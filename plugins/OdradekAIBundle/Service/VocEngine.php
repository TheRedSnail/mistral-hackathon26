<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

/**
 * Voice of Customer (VoC) Analytics Engine.
 *
 * Three-layer pipeline:
 *   1. Multi-source data aggregation (forms, notes, DNC, email engagement)
 *   2. PII redaction (regex + contact-name cross-referencing) — BEFORE AI
 *   3. AI-powered analysis via MistralClient (topic extraction, sentiment, summarisation)
 */
class VocEngine
{
    public function __construct(
        private readonly FormModel     $formModel,
        private readonly LeadModel     $leadModel,
        private readonly ListModel     $listModel,
        private readonly ModelFactory  $modelFactory,
        private readonly MistralClient $mistralClient,
    ) {}

    // =========================================================================
    //  PUBLIC — Data Collection (with automatic PII redaction)
    // =========================================================================

    /**
     * Collect feedback verbatims from one or more Mautic sources.
     * All returned text is PII-redacted.
     *
     * @param string      $source   'forms'|'notes'|'dnc'|'email_engagement'|'all'
     * @param int[]       $formIds  Specific form IDs (applies to forms/all)
     * @param string|null $dateFrom ISO date filter
     * @param string|null $dateTo   ISO date filter
     * @param int         $limit    Max verbatims per source
     * @return array{verbatims: array, sources_queried: string[], total_count: int, contact_ids: int[]}
     */
    public function collectFeedback(
        string $source = 'all',
        array $formIds = [],
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $limit = 100,
    ): array {
        $verbatims  = [];
        $contactIds = [];
        $sources    = [];

        if (in_array($source, ['forms', 'all'], true)) {
            $r          = $this->collectFormSubmissions($formIds, $dateFrom, $dateTo, $limit);
            $verbatims  = array_merge($verbatims, $r['verbatims']);
            $contactIds = array_merge($contactIds, $r['contact_ids']);
            $sources[]  = 'form_submissions';
        }
        if (in_array($source, ['notes', 'all'], true)) {
            $r          = $this->collectContactNotes($dateFrom, $dateTo, $limit);
            $verbatims  = array_merge($verbatims, $r['verbatims']);
            $contactIds = array_merge($contactIds, $r['contact_ids']);
            $sources[]  = 'contact_notes';
        }
        if (in_array($source, ['dnc', 'all'], true)) {
            $r          = $this->collectDncComments($dateFrom, $dateTo, $limit);
            $verbatims  = array_merge($verbatims, $r['verbatims']);
            $contactIds = array_merge($contactIds, $r['contact_ids']);
            $sources[]  = 'dnc_comments';
        }
        if (in_array($source, ['email_engagement', 'all'], true)) {
            $r          = $this->collectEmailEngagement($dateFrom, $dateTo, $limit);
            $verbatims  = array_merge($verbatims, $r['verbatims']);
            $contactIds = array_merge($contactIds, $r['contact_ids']);
            $sources[]  = 'email_engagement';
        }

        // PII redaction pass — build known names, then redact every verbatim
        $contactIds = array_values(array_unique($contactIds));
        $knownNames = $this->buildKnownNames($contactIds);
        foreach ($verbatims as &$v) {
            $v['text'] = $this->redactPii($v['text'], $knownNames);
        }
        unset($v);

        // Sort by date descending
        usort($verbatims, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return [
            'verbatims'       => $verbatims,
            'sources_queried' => $sources,
            'total_count'     => count($verbatims),
            'contact_ids'     => $contactIds,
        ];
    }

    // =========================================================================
    //  PUBLIC — AI Analysis
    // =========================================================================

    /**
     * AI-powered topic extraction + sentiment analysis on collected verbatims.
     *
     * @param array $verbatims Already-redacted verbatim array from collectFeedback()
     */
    public function analyzeThemes(array $verbatims): array
    {
        if (empty($verbatims)) {
            return ['themes' => [], 'overall_sentiment' => 'unknown', 'total_verbatims_analyzed' => 0];
        }

        $sample = array_slice($verbatims, 0, 50);
        $totalCount = count($verbatims);
        $verbatimJson = json_encode(
            array_map(fn ($v) => [
                'text'   => mb_substr($v['text'], 0, 300),
                'source' => $v['source'],
                'date'   => $v['date'],
            ], $sample),
            JSON_PRETTY_PRINT,
        );

        $prompt = "You are a Voice of Customer analytics expert. Analyze these customer feedback verbatims "
            . "and extract the main themes/topics with sentiment analysis per theme.\n\n"
            . "IMPORTANT: All PII has been redacted. Do not attempt to identify individuals.\n\n"
            . "Verbatims ({$totalCount} total, showing " . count($sample) . "):\n"
            . $verbatimJson . "\n\n"
            . "Extract 3-8 themes. For each theme provide:\n"
            . "- name: short theme label (2-4 words)\n"
            . "- sentiment: positive|neutral|negative|mixed\n"
            . "- intensity: 1-10 (how strongly felt)\n"
            . "- count: number of verbatims mentioning this theme\n"
            . "- representative_quotes: 2-3 redacted verbatim excerpts that best illustrate the theme\n"
            . "- trend: rising|stable|declining (infer from dates if possible)\n\n"
            . "Respond ONLY with valid JSON:\n"
            . '{"themes":[{"name":"...","sentiment":"...","intensity":7,"count":12,'
            . '"representative_quotes":["..."],"trend":"stable"}],'
            . '"overall_sentiment":"...","total_verbatims_analyzed":' . count($sample) . '}';

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        return json_decode($raw, true) ?? ['error' => 'Could not parse theme analysis.'];
    }

    /**
     * Deep VoC analysis for a single contact — aggregates ALL feedback sources.
     */
    public function analyzeContactVoice(int $contactId): array
    {
        $lead = $this->leadModel->getEntity($contactId);
        if (!$lead) {
            return ['error' => "Contact #{$contactId} not found."];
        }

        $fields     = $lead->getProfileFields();
        $knownNames = $this->buildKnownNames([$contactId]);
        $verbatims  = [];

        // 1. Form submissions via timeline
        try {
            $engagements = $this->leadModel->getEngagements(
                $lead,
                ['includeEvents' => ['form.submitted']],
                null,
                1,
                50,
                true,
            );
            foreach (($engagements['events'] ?? []) as $event) {
                if (($event['event'] ?? '') !== 'form.submitted') {
                    continue;
                }
                $details   = $event['details'] ?? [];
                $textParts = [];
                foreach ($details as $val) {
                    if (is_string($val) && mb_strlen(trim($val)) > 5) {
                        $textParts[] = $val;
                    }
                }
                if (!empty($textParts)) {
                    $verbatims[] = [
                        'source' => 'form_submission',
                        'text'   => $this->redactPii(implode(' | ', $textParts), $knownNames),
                        'date'   => $event['timestamp'] ?? '',
                    ];
                }
            }
        } catch (\Throwable) {}

        // 2. Contact notes
        try {
            $noteModel = $this->modelFactory->getModel('lead.note');
            $notes     = $noteModel->getEntities([
                'filter' => ['force' => [['column' => 'n.lead', 'value' => $contactId]]],
                'limit'  => 50,
            ]);
            foreach ($notes as $note) {
                $text = $note->getText();
                if (!empty($text) && mb_strlen(trim($text)) > 3) {
                    $verbatims[] = [
                        'source' => 'contact_note',
                        'text'   => $this->redactPii(mb_substr($text, 0, 500), $knownNames),
                        'date'   => $note->getDateTime()?->format('Y-m-d H:i:s') ?? '',
                    ];
                }
            }
        } catch (\Throwable) {}

        // 3. DNC comments
        try {
            $reasonMap = [1 => 'unsubscribed', 2 => 'bounced', 3 => 'manual'];
            foreach ($lead->getDoNotContact() as $dnc) {
                $comment = $dnc->getComments();
                if (!empty($comment)) {
                    $verbatims[] = [
                        'source'   => 'dnc_comment',
                        'text'     => $this->redactPii($comment, $knownNames),
                        'date'     => $dnc->getDateAdded()?->format('Y-m-d H:i:s') ?? '',
                        'metadata' => ['reason' => $reasonMap[$dnc->getReason()] ?? 'unknown'],
                    ];
                }
            }
        } catch (\Throwable) {}

        // 4. Email engagement signals
        $sentCount = $readCount = $failCount = 0;
        try {
            $emailEngagements = $this->leadModel->getEngagements(
                $lead,
                ['includeEvents' => ['email.sent', 'email.read', 'email.failed']],
                null,
                1,
                50,
                true,
            );
            foreach (($emailEngagements['events'] ?? []) as $event) {
                $type = $event['event'] ?? '';
                if ($type === 'email.sent') {
                    $sentCount++;
                }
                if ($type === 'email.read') {
                    $readCount++;
                }
                if ($type === 'email.failed') {
                    $failCount++;
                }
            }
        } catch (\Throwable) {}

        $contactProfile = [
            'verbatims'         => $verbatims,
            'email_engagement'  => [
                'sent'      => $sentCount,
                'opened'    => $readCount,
                'failed'    => $failCount,
                'open_rate' => $sentCount > 0 ? round(($readCount / $sentCount) * 100) : null,
            ],
            'lead_score'        => $lead->getPoints(),
            'days_since_active' => $lead->getLastActive()
                ? (int) (new \DateTime())->diff($lead->getLastActive())->days
                : null,
        ];

        if (empty($verbatims) && $sentCount === 0 && $readCount === 0) {
            return ['error' => 'Contact has no feedback data or engagement history to analyze.'];
        }

        $prompt = "You are a Voice of Customer analyst. Provide a comprehensive VoC profile for this individual contact.\n\n"
            . "IMPORTANT: All PII has been pre-redacted. Do not attempt to re-identify the contact.\n\n"
            . "Contact data:\n" . json_encode($contactProfile, JSON_PRETTY_PRINT) . "\n\n"
            . "Analyze and respond ONLY with valid JSON:\n"
            . '{"sentiment":"positive|neutral|negative|mixed",'
            . '"sentiment_score":72,'
            . '"topics":["topic1","topic2"],'
            . '"churn_signals":["signal1"],'
            . '"engagement_summary":"...",'
            . '"key_quotes":["redacted quote 1"],'
            . '"recommended_action":"...",'
            . '"urgency":"low|medium|high"}';

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        return json_decode($raw, true) ?? ['error' => 'Could not analyze contact voice.'];
    }

    /**
     * Generate a detailed AI summary for a specific theme.
     */
    public function summarizeTheme(string $themeName, array $verbatims): array
    {
        $sample       = array_slice($verbatims, 0, 40);
        $verbatimJson = json_encode(
            array_map(fn ($v) => [
                'text'   => mb_substr($v['text'], 0, 300),
                'source' => $v['source'],
                'date'   => $v['date'],
            ], $sample),
            JSON_PRETTY_PRINT,
        );

        $prompt = "You are a VoC analyst. Provide a detailed summary of the theme \"{$themeName}\" "
            . "based on these customer feedback verbatims.\n\n"
            . "IMPORTANT: All PII has been pre-redacted.\n\n"
            . "Verbatims:\n{$verbatimJson}\n\n"
            . "Focus only on verbatims related to \"{$themeName}\". Respond ONLY with valid JSON:\n"
            . '{"theme":"' . addslashes($themeName) . '","summary":"2-3 sentence summary",'
            . '"representative_quotes":["quote1","quote2","quote3"],'
            . '"trend":"rising|stable|declining","severity":"low|medium|high|critical",'
            . '"recommended_action":"specific actionable recommendation",'
            . '"affected_contact_count":0}';

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        return json_decode($raw, true) ?? ['error' => 'Could not summarize theme.'];
    }

    // =========================================================================
    //  PUBLIC — Action: Segment Creation
    // =========================================================================

    /**
     * Create a static Mautic segment from contact IDs surfaced by VoC analysis.
     */
    public function createInsightSegment(string $name, string $description, array $contactIds): array
    {
        $segment = $this->listModel->getEntity();
        $segment->setName($name);
        $segment->setDescription($description);
        $segment->setIsGlobal(true);
        $segment->setIsPublished(true);
        $this->listModel->saveEntity($segment);

        $addedCount = 0;
        foreach ($contactIds as $contactId) {
            $lead = $this->leadModel->getEntity((int) $contactId);
            if ($lead) {
                $this->listModel->addLead($lead, $segment, true);
                $addedCount++;
            }
        }

        return [
            'segment_id'     => $segment->getId(),
            'segment_name'   => $segment->getName(),
            'contacts_added' => $addedCount,
            'link'           => '/s/segments/view/' . $segment->getId(),
        ];
    }

    // =========================================================================
    //  PRIVATE — PII Redaction Engine
    // =========================================================================

    /**
     * Master PII redaction entry point.
     * Phase 1: Pattern-based regex (stateless)
     * Phase 2: Contact name cross-referencing (stateful)
     */
    private function redactPii(string $text, array $knownNames = []): string
    {
        // Phase 1: Pattern-based redaction
        $text = $this->redactEmails($text);
        $text = $this->redactPhones($text);
        $text = $this->redactSsns($text);
        $text = $this->redactCreditCards($text);
        $text = $this->redactIpAddresses($text);
        $text = $this->redactUrls($text);

        // Phase 2: Name cross-referencing
        $text = $this->redactKnownNames($text, $knownNames);

        return $text;
    }

    private function redactEmails(string $text): string
    {
        return preg_replace(
            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            '[EMAIL_REDACTED]',
            $text,
        ) ?? $text;
    }

    private function redactPhones(string $text): string
    {
        return preg_replace(
            '/(?:\+?\d{1,3}[\s\-.]?)?\(?\d{2,4}\)?[\s\-.]?\d{3,4}[\s\-.]?\d{3,4}/',
            '[PHONE_REDACTED]',
            $text,
        ) ?? $text;
    }

    private function redactSsns(string $text): string
    {
        return preg_replace('/\b\d{3}-\d{2}-\d{4}\b/', '[ID_REDACTED]', $text) ?? $text;
    }

    private function redactCreditCards(string $text): string
    {
        return preg_replace('/\b(?:\d[\s\-]?){13,19}\b/', '[CARD_REDACTED]', $text) ?? $text;
    }

    private function redactIpAddresses(string $text): string
    {
        // IPv4
        $text = preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', '[IP_REDACTED]', $text) ?? $text;
        // IPv6 (simplified)
        $text = preg_replace('/\b(?:[0-9a-fA-F]{1,4}:){2,7}[0-9a-fA-F]{1,4}\b/', '[IP_REDACTED]', $text) ?? $text;

        return $text;
    }

    private function redactUrls(string $text): string
    {
        return preg_replace_callback(
            '/https?:\/\/[^\s]+/',
            function (array $match): string {
                $url   = $match[0];
                $parts = parse_url($url);
                if (!empty($parts['query'])) {
                    $base = str_replace('?' . $parts['query'], '', $url);
                    return $base . '?[PARAMS_REDACTED]';
                }
                return $url;
            },
            $text,
        ) ?? $text;
    }

    private function redactKnownNames(string $text, array $knownNames): string
    {
        // Sort longest-first to avoid partial matches
        usort($knownNames, fn ($a, $b) => mb_strlen($b) - mb_strlen($a));
        foreach ($knownNames as $name) {
            if (mb_strlen($name) < 2) {
                continue;
            }
            $text = str_ireplace($name, '[NAME_REDACTED]', $text);
        }
        return $text;
    }

    /**
     * Build a list of known contact names for Phase 2 cross-referencing.
     *
     * @param int[] $contactIds
     * @return string[]
     */
    private function buildKnownNames(array $contactIds): array
    {
        $names = [];
        // Limit to 200 contacts to avoid excessive DB queries
        foreach (array_slice($contactIds, 0, 200) as $id) {
            try {
                $lead = $this->leadModel->getEntity((int) $id);
                if (!$lead) {
                    continue;
                }
                $fields = $lead->getProfileFields();
                $first  = trim($fields['firstname'] ?? '');
                $last   = trim($fields['lastname'] ?? '');
                if ($first !== '') {
                    $names[] = $first;
                }
                if ($last !== '') {
                    $names[] = $last;
                }
                if ($first !== '' && $last !== '') {
                    $names[] = $first . ' ' . $last;
                }
            } catch (\Throwable) {}
        }
        return array_values(array_unique($names));
    }

    // =========================================================================
    //  PRIVATE — Source Collectors
    // =========================================================================

    /**
     * Collect text/textarea responses from form submissions.
     */
    private function collectFormSubmissions(
        array $formIds,
        ?string $dateFrom,
        ?string $dateTo,
        int $limit,
    ): array {
        $verbatims  = [];
        $contactIds = [];

        // If no specific form IDs, get all published forms
        if (empty($formIds)) {
            $forms = $this->formModel->getEntities([
                'limit'  => 50,
                'filter' => ['string' => ''],
            ]);
            foreach ($forms as $form) {
                $formIds[] = $form->getId();
            }
        }

        $submissionModel = $this->modelFactory->getModel('form.submission');

        foreach ($formIds as $formId) {
            $form = $this->formModel->getEntity((int) $formId);
            if (!$form) {
                continue;
            }

            // Identify text-bearing fields
            $textFieldAliases = [];
            foreach ($form->getFields() as $field) {
                if (in_array($field->getType(), ['text', 'textarea', 'email', 'tel', 'url'], true)) {
                    $textFieldAliases[] = $field->getAlias();
                }
            }
            if (empty($textFieldAliases)) {
                continue;
            }

            try {
                $submissions = $submissionModel->getRepository()->getEntities([
                    'form'  => $form,
                    'limit' => $limit,
                    'start' => 0,
                ]);
            } catch (\Throwable) {
                continue;
            }

            foreach ($submissions as $submission) {
                $results       = $submission->getResults();
                $dateSubmitted = $submission->getDateSubmitted();

                // Date filtering
                if ($dateFrom && $dateSubmitted < new \DateTime($dateFrom)) {
                    continue;
                }
                if ($dateTo && $dateSubmitted > new \DateTime($dateTo)) {
                    continue;
                }

                $textParts = [];
                foreach ($textFieldAliases as $alias) {
                    $val = $results[$alias] ?? '';
                    if (is_string($val) && mb_strlen(trim($val)) > 3) {
                        $textParts[] = $val;
                    }
                }
                if (empty($textParts)) {
                    continue;
                }

                $contactId = $submission->getLead()?->getId();
                if ($contactId) {
                    $contactIds[] = $contactId;
                }

                $verbatims[] = [
                    'source'     => 'form_submission',
                    'text'       => implode(' | ', $textParts),
                    'contact_id' => $contactId,
                    'date'       => $dateSubmitted->format('Y-m-d H:i:s'),
                    'metadata'   => [
                        'form_id'   => $formId,
                        'form_name' => $form->getName(),
                    ],
                ];
            }
        }

        return ['verbatims' => $verbatims, 'contact_ids' => $contactIds];
    }

    /**
     * Collect contact notes (free-text entries from sales/support staff).
     */
    private function collectContactNotes(
        ?string $dateFrom,
        ?string $dateTo,
        int $limit,
    ): array {
        $verbatims  = [];
        $contactIds = [];

        try {
            $noteModel = $this->modelFactory->getModel('lead.note');
            $notes     = $noteModel->getEntities([
                'limit'      => $limit,
                'start'      => 0,
                'orderBy'    => 'n.dateTime',
                'orderByDir' => 'DESC',
            ]);

            foreach ($notes as $note) {
                $text = $note->getText();
                if (empty($text) || mb_strlen(trim($text)) < 5) {
                    continue;
                }

                $noteDate = $note->getDateTime();
                if ($dateFrom && $noteDate && $noteDate < new \DateTime($dateFrom)) {
                    continue;
                }
                if ($dateTo && $noteDate && $noteDate > new \DateTime($dateTo)) {
                    continue;
                }

                $contactId = $note->getLead()?->getId();
                if ($contactId) {
                    $contactIds[] = $contactId;
                }

                $verbatims[] = [
                    'source'     => 'contact_note',
                    'text'       => mb_substr($text, 0, 500),
                    'contact_id' => $contactId,
                    'date'       => $noteDate?->format('Y-m-d H:i:s') ?? '',
                    'metadata'   => ['note_type' => $note->getType()],
                ];
            }
        } catch (\Throwable) {}

        return ['verbatims' => $verbatims, 'contact_ids' => $contactIds];
    }

    /**
     * Collect DNC (Do Not Contact) comments — unsubscribe reasons and bounce notes.
     */
    private function collectDncComments(
        ?string $dateFrom,
        ?string $dateTo,
        int $limit,
    ): array {
        $verbatims  = [];
        $contactIds = [];

        try {
            $leads = $this->leadModel->getEntities([
                'limit'      => $limit * 2,
                'start'      => 0,
                'orderBy'    => 'l.dateModified',
                'orderByDir' => 'DESC',
            ]);

            $count     = 0;
            $reasonMap = [1 => 'unsubscribed', 2 => 'bounced', 3 => 'manual'];

            foreach ($leads as $lead) {
                foreach ($lead->getDoNotContact() as $dnc) {
                    $comment = $dnc->getComments();
                    if (empty($comment) || mb_strlen(trim($comment)) < 3) {
                        continue;
                    }

                    $dncDate = $dnc->getDateAdded();
                    if ($dateFrom && $dncDate && $dncDate < new \DateTime($dateFrom)) {
                        continue;
                    }
                    if ($dateTo && $dncDate && $dncDate > new \DateTime($dateTo)) {
                        continue;
                    }

                    $contactId    = $lead->getId();
                    $contactIds[] = $contactId;

                    $verbatims[] = [
                        'source'     => 'dnc_comment',
                        'text'       => mb_substr($comment, 0, 500),
                        'contact_id' => $contactId,
                        'date'       => $dncDate?->format('Y-m-d H:i:s') ?? '',
                        'metadata'   => [
                            'reason'  => $reasonMap[$dnc->getReason()] ?? 'unknown',
                            'channel' => $dnc->getChannel(),
                        ],
                    ];

                    if (++$count >= $limit) {
                        break 2;
                    }
                }
            }
        } catch (\Throwable) {}

        return ['verbatims' => $verbatims, 'contact_ids' => $contactIds];
    }

    /**
     * Collect email engagement signals as synthesised text.
     * Transforms behavioural signals (non-opens, bounces) into statements the AI can reason about.
     */
    private function collectEmailEngagement(
        ?string $dateFrom,
        ?string $dateTo,
        int $limit,
    ): array {
        $verbatims  = [];
        $contactIds = [];

        try {
            $leads = $this->leadModel->getEntities([
                'limit'      => $limit,
                'start'      => 0,
                'orderBy'    => 'l.lastActive',
                'orderByDir' => 'DESC',
            ]);

            foreach ($leads as $lead) {
                $engagements = $this->leadModel->getEngagements(
                    $lead,
                    ['includeEvents' => ['email.sent', 'email.read', 'email.failed']],
                    null,
                    1,
                    20,
                    true,
                );

                $events = $engagements['events'] ?? [];
                if (empty($events)) {
                    continue;
                }

                $sentCount = $readCount = $failCount = 0;
                foreach ($events as $event) {
                    $type = $event['event'] ?? '';
                    if ($type === 'email.sent') {
                        $sentCount++;
                    }
                    if ($type === 'email.read') {
                        $readCount++;
                    }
                    if ($type === 'email.failed') {
                        $failCount++;
                    }
                }

                $signals = [];
                if ($sentCount > 0 && $readCount === 0) {
                    $signals[] = "Contact was sent {$sentCount} emails but opened none (disengaged)";
                } elseif ($sentCount > 0 && $readCount > 0) {
                    $rate      = round(($readCount / $sentCount) * 100);
                    $signals[] = "Email open rate: {$rate}% ({$readCount}/{$sentCount})";
                }
                if ($failCount > 0) {
                    $signals[] = "Had {$failCount} failed/bounced email delivery(ies)";
                }

                if (empty($signals)) {
                    continue;
                }

                $contactId    = $lead->getId();
                $contactIds[] = $contactId;

                $verbatims[] = [
                    'source'     => 'email_engagement',
                    'text'       => implode('. ', $signals),
                    'contact_id' => $contactId,
                    'date'       => $lead->getLastActive()?->format('Y-m-d H:i:s') ?? '',
                    'metadata'   => [
                        'sent'   => $sentCount,
                        'opened' => $readCount,
                        'failed' => $failCount,
                    ],
                ];
            }
        } catch (\Throwable) {}

        return ['verbatims' => $verbatims, 'contact_ids' => $contactIds];
    }
}

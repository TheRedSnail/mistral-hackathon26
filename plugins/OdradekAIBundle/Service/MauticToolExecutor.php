<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class MauticToolExecutor
{
    public function __construct(
        private readonly LeadModel     $leadModel,
        private readonly EmailModel    $emailModel,
        private readonly CampaignModel $campaignModel,
        private readonly ListModel     $listModel,
        private readonly ModelFactory  $modelFactory,
        private readonly MistralClient $mistralClient,
    ) {}

    public function execute(string $tool, array $args): array
    {
        // Client-side tools — frontend handles these
        if (in_array($tool, ['navigate_mautic', 'get_page_info'], true)) {
            return ['client_side' => true, 'tool' => $tool, 'args' => $args];
        }

        try {
            return match ($tool) {
                'list_contacts'  => $this->listContacts($args),
                'get_contact'    => $this->getContact($args),
                'create_contact' => $this->createContact($args),
                'update_contact' => $this->updateContact($args),
                'delete_contact' => $this->deleteContact($args),
                'list_emails'    => $this->listEmails($args),
                'get_email'      => $this->getEmail($args),
                'create_email'   => $this->createEmail($args),
                'update_email'   => $this->updateEmail($args),
                'list_campaigns' => $this->listCampaigns($args),
                'get_campaign'   => $this->getCampaign($args),
                'list_segments'  => $this->listSegments($args),
                'create_segment' => $this->createSegment($args),
                'list_reports'              => $this->listReports(),
                'get_report_data'           => $this->getReportData($args),
                'analyze_email_ethics'      => $this->analyzeEmailEthics($args),
                'analyze_campaign_performance' => $this->analyzeCampaignPerformance($args),
                'suggest_campaign_journey'  => $this->suggestCampaignJourney($args),
                default                     => ['success' => false, 'error' => "Unknown tool: {$tool}"],
            };
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    private function listContacts(array $args): array
    {
        $limit  = (int) ($args['limit'] ?? 20);
        $search = $args['search'] ?? '';

        $options = [
            'start'  => 0,
            'limit'  => $limit,
            'filter' => ['string' => $search],
        ];

        $leads = $this->leadModel->getEntities($options);
        $data  = [];

        foreach ($leads as $lead) {
            $fields        = $lead->getProfileFields();
            $data[] = [
                'id'        => $lead->getId(),
                'firstname' => $fields['firstname'] ?? '',
                'lastname'  => $fields['lastname'] ?? '',
                'email'     => $fields['email'] ?? '',
                'company'   => $fields['company'] ?? '',
                'score'     => $lead->getPoints(),
            ];
        }

        return ['success' => true, 'contacts' => $data, 'count' => count($data)];
    }

    private function getContact(array $args): array
    {
        $lead = $this->leadModel->getEntity((int) $args['id']);
        if (!$lead) {
            return ['success' => false, 'error' => "Contact #{$args['id']} not found."];
        }

        $fields = $lead->getProfileFields();

        return [
            'success' => true,
            'contact' => [
                'id'         => $lead->getId(),
                'fields'     => $fields,
                'score'      => $lead->getPoints(),
                'created_at' => $lead->getDateAdded()?->format('Y-m-d H:i:s'),
                'owner'      => $lead->getOwner()?->getUsername(),
            ],
        ];
    }

    private function createContact(array $args): array
    {
        $fields = $args['fields'] ?? [];
        if (empty($fields['email']) && empty($fields['firstname']) && empty($fields['lastname'])) {
            return ['success' => false, 'error' => 'At minimum, provide email or name fields.'];
        }

        $lead = $this->leadModel->checkForDuplicateContact($fields, null, true, true);

        $this->leadModel->setFieldValues($lead, $fields, true, true, true);
        $this->leadModel->saveEntity($lead);

        return [
            'success' => true,
            'contact' => ['id' => $lead->getId(), 'fields' => $fields],
            'message' => "Contact created with ID #{$lead->getId()}.",
        ];
    }

    private function updateContact(array $args): array
    {
        $lead = $this->leadModel->getEntity((int) $args['id']);
        if (!$lead) {
            return ['success' => false, 'error' => "Contact #{$args['id']} not found."];
        }

        $fields = $args['fields'] ?? [];
        $this->leadModel->setFieldValues($lead, $fields, false, true, true);
        $this->leadModel->saveEntity($lead);

        return [
            'success' => true,
            'message' => "Contact #{$args['id']} updated.",
            'fields'  => $fields,
        ];
    }

    private function deleteContact(array $args): array
    {
        $lead = $this->leadModel->getEntity((int) $args['id']);
        if (!$lead) {
            return ['success' => false, 'error' => "Contact #{$args['id']} not found."];
        }

        $this->leadModel->deleteEntity($lead);

        return ['success' => true, 'message' => "Contact #{$args['id']} permanently deleted."];
    }

    // ── Emails ────────────────────────────────────────────────────────────────

    private function listEmails(array $args): array
    {
        $limit  = (int) ($args['limit'] ?? 20);
        $search = $args['search'] ?? '';

        $emails = $this->emailModel->getEntities([
            'start'  => 0,
            'limit'  => $limit,
            'filter' => ['string' => $search],
        ]);

        $data = [];
        foreach ($emails as $email) {
            $data[] = [
                'id'          => $email->getId(),
                'name'        => $email->getName(),
                'subject'     => $email->getSubject(),
                'from_name'   => $email->getFromName(),
                'from_email'  => $email->getFromAddress(),
                'sent_count'  => $email->getSentCount(),
                'open_count'  => $email->getReadCount(),
            ];
        }

        return ['success' => true, 'emails' => $data, 'count' => count($data)];
    }

    private function getEmail(array $args): array
    {
        $email = $this->emailModel->getEntity((int) $args['id']);
        if (!$email) {
            return ['success' => false, 'error' => "Email #{$args['id']} not found."];
        }

        return [
            'success' => true,
            'email'   => [
                'id'          => $email->getId(),
                'name'        => $email->getName(),
                'subject'     => $email->getSubject(),
                'body'        => $email->getCustomHtml(),
                'from_name'   => $email->getFromName(),
                'from_email'  => $email->getFromAddress(),
                'sent_count'  => $email->getSentCount(),
                'open_count'  => $email->getReadCount(),
                'created_at'  => $email->getDateAdded()?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    private function createEmail(array $args): array
    {
        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $this->emailModel->getEntity();
        $email->setName($args['name']);
        $email->setSubject($args['subject']);
        $email->setCustomHtml($args['body'] ?? '');

        if (!empty($args['fromName'])) {
            $email->setFromName($args['fromName']);
        }
        if (!empty($args['fromEmail'])) {
            $email->setFromAddress($args['fromEmail']);
        }

        $email->setEmailType('template');

        $this->emailModel->saveEntity($email);

        return [
            'success' => true,
            'email'   => ['id' => $email->getId(), 'name' => $email->getName()],
            'message' => "Email \"{$args['name']}\" created with ID #{$email->getId()}.",
        ];
    }

    private function updateEmail(array $args): array
    {
        $email = $this->emailModel->getEntity((int) $args['id']);
        if (!$email) {
            return ['success' => false, 'error' => "Email #{$args['id']} not found."];
        }

        $params = $args['params'] ?? [];

        if (isset($params['name'])) {
            $email->setName($params['name']);
        }
        if (isset($params['subject'])) {
            $email->setSubject($params['subject']);
        }
        if (isset($params['body'])) {
            $email->setCustomHtml($params['body']);
        }
        if (isset($params['fromName'])) {
            $email->setFromName($params['fromName']);
        }
        if (isset($params['fromEmail'])) {
            $email->setFromAddress($params['fromEmail']);
        }

        $this->emailModel->saveEntity($email);

        return ['success' => true, 'message' => "Email #{$args['id']} updated."];
    }

    // ── Campaigns ─────────────────────────────────────────────────────────────

    private function listCampaigns(array $args): array
    {
        $limit  = (int) ($args['limit'] ?? 20);
        $search = $args['search'] ?? '';

        $campaigns = $this->campaignModel->getEntities([
            'start'  => 0,
            'limit'  => $limit,
            'filter' => ['string' => $search],
        ]);

        $data = [];
        foreach ($campaigns as $campaign) {
            $data[] = [
                'id'          => $campaign->getId(),
                'name'        => $campaign->getName(),
                'description' => $campaign->getDescription(),
                'is_published' => $campaign->isPublished(),
                'created_at'  => $campaign->getDateAdded()?->format('Y-m-d H:i:s'),
            ];
        }

        return ['success' => true, 'campaigns' => $data, 'count' => count($data)];
    }

    private function getCampaign(array $args): array
    {
        $campaign = $this->campaignModel->getEntity((int) $args['id']);
        if (!$campaign) {
            return ['success' => false, 'error' => "Campaign #{$args['id']} not found."];
        }

        return [
            'success'  => true,
            'campaign' => [
                'id'           => $campaign->getId(),
                'name'         => $campaign->getName(),
                'description'  => $campaign->getDescription(),
                'is_published' => $campaign->isPublished(),
                'created_at'   => $campaign->getDateAdded()?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    // ── Segments ──────────────────────────────────────────────────────────────

    private function listSegments(array $args): array
    {
        $limit  = (int) ($args['limit'] ?? 20);
        $search = $args['search'] ?? '';

        $segments = $this->listModel->getEntities([
            'start'  => 0,
            'limit'  => $limit,
            'filter' => ['string' => $search],
        ]);

        $data = [];
        foreach ($segments as $segment) {
            $data[] = [
                'id'    => $segment->getId(),
                'name'  => $segment->getName(),
                'alias' => $segment->getAlias(),
                'count' => $segment->getLeadCount(),
            ];
        }

        return ['success' => true, 'segments' => $data, 'count' => count($data)];
    }

    private function createSegment(array $args): array
    {
        /** @var \Mautic\LeadBundle\Entity\LeadList $segment */
        $segment = $this->listModel->getEntity();
        $segment->setName($args['name']);

        if (!empty($args['alias'])) {
            $segment->setAlias($args['alias']);
        }

        $this->listModel->saveEntity($segment);

        return [
            'success' => true,
            'segment' => ['id' => $segment->getId(), 'name' => $segment->getName(), 'alias' => $segment->getAlias()],
            'message' => "Segment \"{$args['name']}\" created with ID #{$segment->getId()}.",
        ];
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    private function listReports(): array
    {
        /** @var \Mautic\ReportBundle\Model\ReportModel $reportModel */
        $reportModel = $this->modelFactory->getModel('report');
        $reports     = $reportModel->getEntities(['start' => 0, 'limit' => 50]);

        $data = [];
        foreach ($reports as $report) {
            $data[] = [
                'id'          => $report->getId(),
                'name'        => $report->getName(),
                'description' => $report->getDescription(),
            ];
        }

        return ['success' => true, 'reports' => $data, 'count' => count($data)];
    }

    private function getReportData(array $args): array
    {
        /** @var \Mautic\ReportBundle\Model\ReportModel $reportModel */
        $reportModel = $this->modelFactory->getModel('report');
        $report      = $reportModel->getEntity((int) $args['id']);

        if (!$report) {
            return ['success' => false, 'error' => "Report #{$args['id']} not found."];
        }

        // Build report data
        $options = ['limit' => 50, 'paginate' => false];
        $data    = $reportModel->runReport($report, $options);

        return [
            'success' => true,
            'report'  => [
                'id'    => $report->getId(),
                'name'  => $report->getName(),
                'rows'  => $data['data'] ?? [],
                'total' => $data['totalResults'] ?? 0,
            ],
        ];
    }

    // ── Ethics & Intelligence ─────────────────────────────────────────────────

    private function analyzeEmailEthics(array $args): array
    {
        $content   = '';
        $emailName = '';
        $subject   = '';

        if (!empty($args['email_id'])) {
            $email = $this->emailModel->getEntity((int) $args['email_id']);
            if (!$email) {
                return ['success' => false, 'error' => "Email #{$args['email_id']} not found."];
            }
            $content   = strip_tags($email->getCustomHtml() ?: $email->getPlainText() ?: '');
            $emailName = $email->getName();
            $subject   = $email->getSubject();
        } elseif (!empty($args['content'])) {
            $content = strip_tags($args['content']);
        } else {
            return ['success' => false, 'error' => 'Provide either email_id or content.'];
        }

        if (empty(trim($content))) {
            return ['success' => false, 'error' => 'Email has no text content to analyze.'];
        }

        $subjectLine = $subject ? "Subject line: \"{$subject}\"\n" : '';
        $prompt = "You are an ethical marketing AI auditor. Analyze this marketing email for dark patterns "
            . "and EU AI Act / GDPR compliance issues.\n\n"
            . $subjectLine
            . "Look for:\n"
            . "1. False urgency (fake deadlines, \"act now\" without real cause)\n"
            . "2. Scarcity manipulation (unverified \"only X left\" claims)\n"
            . "3. Guilt-tripping or emotional manipulation\n"
            . "4. Misleading claims or hidden costs\n"
            . "5. Overly aggressive or pressure-based CTAs\n"
            . "6. GDPR concerns (missing unsubscribe option, unclear data use)\n\n"
            . "Respond ONLY with valid JSON in this exact format:\n"
            . '{"ethics_score":85,"issues":[{"type":"false_urgency","severity":"medium","excerpt":"...","recommendation":"..."}],"overall_severity":"low","summary":"...","eu_ai_act_compliant":true}'
            . "\n\nEmail body:\n"
            . mb_substr($content, 0, 3000);

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $analysis = json_decode($raw, true) ?? ['error' => 'Could not parse ethics analysis.'];

        return [
            'success'    => true,
            'email_name' => $emailName,
            'analysis'   => $analysis,
        ];
    }

    private function analyzeCampaignPerformance(array $args): array
    {
        $campaign = $this->campaignModel->getEntity((int) $args['campaign_id']);
        if (!$campaign) {
            return ['success' => false, 'error' => "Campaign #{$args['campaign_id']} not found."];
        }

        $emailMetrics = [];
        foreach ($campaign->getEvents() as $event) {
            if ($event->getType() === 'email.send') {
                $props   = $event->getProperties();
                $emailId = $props['email'] ?? null;
                if ($emailId) {
                    $email = $this->emailModel->getEntity((int) $emailId);
                    if ($email) {
                        $sent   = $email->getSentCount();
                        $opened = $email->getReadCount();
                        $emailMetrics[] = [
                            'name'      => $email->getName(),
                            'subject'   => $email->getSubject(),
                            'sent'      => $sent,
                            'opened'    => $opened,
                            'open_rate' => $sent > 0 ? round(($opened / $sent) * 100, 1) . '%' : 'N/A',
                        ];
                    }
                }
            }
        }

        $campaignData = [
            'name'        => $campaign->getName(),
            'published'   => $campaign->isPublished(),
            'lead_count'  => $campaign->getLeadCount(),
            'emails'      => $emailMetrics,
        ];

        $prompt = "You are a marketing analytics expert. Analyze this Mautic campaign data and give actionable insights.\n\n"
            . 'Campaign data: ' . json_encode($campaignData, JSON_PRETTY_PRINT) . "\n\n"
            . "Provide:\n"
            . "1. Overall performance assessment\n"
            . "2. What is working well\n"
            . "3. What needs improvement (with specific suggestions)\n"
            . "4. Recommended next steps\n\n"
            . "Be specific, data-driven, and concise. Use bullet points.";

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        return [
            'success'  => true,
            'campaign' => $campaignData,
            'insights' => $response['content'] ?? 'Could not generate insights.',
        ];
    }

    private function suggestCampaignJourney(array $args): array
    {
        $goal     = $args['goal'] ?? '';
        $audience = $args['audience'] ?? 'general subscribers';
        $count    = min((int) ($args['num_emails'] ?? 3), 6);

        $prompt = "You are an expert email marketing strategist. Design a {$count}-email journey.\n\n"
            . "Goal: {$goal}\n"
            . "Audience: {$audience}\n\n"
            . "Respond ONLY with valid JSON:\n"
            . '{"journey_name":"...","goal":"...","emails":[{"step":1,"delay":"Immediately","subject":"...","purpose":"...","key_message":"...","cta":"..."}]}'
            . "\n\nMake subjects compelling, timing realistic, messaging ethical and non-manipulative.";

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $journey = json_decode($raw, true) ?? ['error' => 'Could not generate journey plan.'];

        return [
            'success' => true,
            'journey' => $journey,
        ];
    }
}

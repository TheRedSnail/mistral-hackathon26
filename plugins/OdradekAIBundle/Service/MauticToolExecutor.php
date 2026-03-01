<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\PageBundle\Model\PageModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;

class MauticToolExecutor
{
    public function __construct(
        private readonly LeadModel            $leadModel,
        private readonly EmailModel           $emailModel,
        private readonly CampaignModel        $campaignModel,
        private readonly ListModel            $listModel,
        private readonly ModelFactory         $modelFactory,
        private readonly GrapesJsBuilderModel $grapesJsBuilderModel,
        private readonly MistralClient        $mistralClient,
        private readonly GeminiClient         $geminiClient,
        private readonly CoreParametersHelper $parametersHelper,
        private readonly PageModel            $pageModel,
        private readonly FormModel            $formModel,
        private readonly VocEngine            $vocEngine,
    ) {}

    public function execute(string $tool, array $args): array
    {
        // Client-side tools — frontend handles these
        if (in_array($tool, ['navigate_mautic', 'get_page_info', 'update_grapesjs_component'], true)) {
            return ['client_side' => true, 'tool' => $tool, 'args' => $args];
        }

        try {
            return match ($tool) {
                'list_contacts'  => $this->listContacts($args),
                'get_contact'    => $this->getContact($args),
                'create_contact' => $this->createContact($args),
                'update_contact' => $this->updateContact($args),
                'delete_contact' => $this->deleteContact($args),
                'list_email_themes' => $this->listEmailThemes(),
                'list_emails'    => $this->listEmails($args),
                'get_email'      => $this->getEmail($args),
                'create_email'          => $this->createEmail($args),
                'update_email'          => $this->updateEmail($args),
                'get_email_components'  => $this->getEmailComponents($args),
                'update_email_component'         => $this->updateEmailComponent($args),
                'get_email_image_components'    => $this->getEmailImageComponents($args),
                'update_email_image_component'  => $this->updateEmailImageComponent($args),
                'localize_email'                => $this->localizeEmail($args),
                'list_campaigns' => $this->listCampaigns($args),
                'get_campaign'   => $this->getCampaign($args),
                'list_segments'              => $this->listSegments($args),
                'get_segment'               => $this->getSegment($args),
                'get_segment_filter_fields' => $this->getSegmentFilterFields(),
                'create_segment'            => $this->createSegment($args),
                'update_segment'            => $this->updateSegment($args),
                'list_reports'              => $this->listReports(),
                'get_report_data'           => $this->getReportData($args),
                'analyze_email_ethics'         => $this->analyzeEmailEthics($args),
                'analyze_campaign_performance' => $this->analyzeCampaignPerformance($args),
                'suggest_campaign_journey'     => $this->suggestCampaignJourney($args),
                'generate_compliance_report'   => $this->generateComplianceReport($args),
                'analyze_contact_sentiment'    => $this->analyzeContactSentiment($args),
                'score_contact_health'         => $this->scoreContactHealth($args),
                'list_assets'           => $this->listAssets($args),
                'get_asset'             => $this->getAsset($args),
                'list_asset_categories' => $this->listAssetCategories(),
                'create_asset_category' => $this->createAssetCategory($args),
                'generate_image_asset'  => $this->generateImageAsset($args),
                'update_asset'          => $this->updateAsset($args),
                'list_page_themes' => $this->listPageThemes(),
                'list_pages'       => $this->listPages($args),
                'get_page'         => $this->getPage($args),
                'create_page'      => $this->createPage($args),
                'update_page'      => $this->updatePage($args),
                'list_forms'       => $this->listForms($args),
                'get_form'         => $this->getForm($args),
                'create_form'      => $this->createForm($args),
                'update_form'      => $this->updateForm($args),
                'voc_collect_feedback'          => $this->vocCollectFeedback($args),
                'voc_analyze_themes'            => $this->vocAnalyzeThemes($args),
                'voc_contact_voice'             => $this->vocContactVoice($args),
                'voc_summarize_theme'           => $this->vocSummarizeTheme($args),
                'voc_create_insight_segment'    => $this->vocCreateInsightSegment($args),
                'voc_suggest_response_campaign' => $this->vocSuggestResponseCampaign($args),
                'list_survey_templates'         => $this->listSurveyTemplates(),
                'create_survey'                 => $this->createSurvey($args),
                'survey_analytics'              => $this->surveyAnalytics($args),
                default            => ['success' => false, 'error' => "Unknown tool: {$tool}"],
            };
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Contacts ─────────────────────────────────────────────────────────────

    private function listContacts(array $args): array
    {
        $limit  = min((int) ($args['limit'] ?? 20), 200);
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
        $limit  = min((int) ($args['limit'] ?? 20), 200);
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

    private function listEmailThemes(): array
    {
        $themesDir = dirname(__DIR__, 3) . '/themes';
        if (!is_dir($themesDir)) {
            return ['success' => false, 'error' => 'Themes directory not found at: ' . $themesDir];
        }

        $themes = [];
        foreach (glob($themesDir . '/*/config.json') ?: [] as $configFile) {
            $config   = json_decode(file_get_contents($configFile), true) ?? [];
            $features = $config['features'] ?? [];
            $builders = $config['builder']  ?? [];

            // Only include themes that appear in the Mautic GrapesJS email builder UI
            if (!in_array('email', $features, true)) {
                continue;
            }
            if (!in_array('grapesjsbuilder', $builders, true)) {
                continue;
            }

            $themes[] = [
                'name'  => basename(dirname($configFile)),
                'label' => $config['name'] ?? basename(dirname($configFile)),
            ];
        }

        return ['success' => true, 'themes' => $themes, 'count' => count($themes)];
    }

    private function createEmail(array $args): array
    {
        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $this->emailModel->getEntity();
        $email->setName($args['name']);
        $email->setSubject($args['subject']);

        if (!empty($args['fromName']))  { $email->setFromName($args['fromName']); }
        if (!empty($args['fromEmail'])) { $email->setFromAddress($args['fromEmail']); }

        $template = $args['template'] ?? '';
        $body     = $args['body'] ?? '';

        if ($template) {
            $email->setTemplate($template);
        }

        // customHtml is the fallback HTML used for sending before the builder compiles the MJML
        $email->setCustomHtml($this->wrapEmailBody($args['subject'], $this->sanitizeEmailHtml($body)));
        $email->setEmailType('template');
        $this->emailModel->saveEntity($email);

        // Store theme MJML in bundle_grapesjsbuilder so the GrapesJS builder opens in MJML mode
        if ($template) {
            $mjml = $this->loadThemeMjml($template, $body);
            if ($mjml !== null) {
                $gjsEntity = new GrapesJsBuilder();
                $gjsEntity->setEmail($email);
                $gjsEntity->setCustomMjml($mjml);
                $this->grapesJsBuilderModel->getRepository()->saveEntity($gjsEntity);
            }
        }

        return [
            'success' => true,
            'email'   => ['id' => $email->getId(), 'name' => $email->getName()],
            'message' => "Email \"{$args['name']}\" created with ID #{$email->getId()}."
                . ($template ? " (theme: {$template})" : ''),
        ];
    }

    private function localizeEmail(array $args): array
    {
        $sourceId = (int) $args['sourceId'];
        $locale   = strtoupper(trim($args['locale']));
        $language = $args['language'] ?? $locale;

        $source = $this->emailModel->getEntity($sourceId);
        if (!$source) {
            return ['success' => false, 'error' => "Email #{$sourceId} not found."];
        }

        $newName = rtrim($source->getName()) . ' (' . $locale . ')';

        // Idempotency: if a copy with this exact name already exists, return it without creating a duplicate.
        // Must use findOneBy (exact match) — getEntities with filter does a LIKE search and would
        // match the source email itself (e.g. "New Leads" matches "New Leads (NL)").
        $existingEmail = $this->emailModel->getRepository()->findOneBy(['name' => $newName]);
        if ($existingEmail !== null) {
            return [
                'success'  => true,
                'email'    => ['id' => $existingEmail->getId(), 'name' => $newName],
                'locale'   => $locale,
                'language' => $language,
                'sourceId' => $sourceId,
                'message'  => "Email \"{$newName}\" (#{$existingEmail->getId()}) already exists — call get_email_components with this ID to retrieve the slots, then translate each with update_email_component.",
            ];
        }

        /** @var \Mautic\EmailBundle\Entity\Email $newEmail */
        $newEmail = $this->emailModel->getEntity();
        $newEmail->setName($newName);
        $newEmail->setSubject($source->getSubject());
        $newEmail->setEmailType($source->getEmailType() ?? 'template');
        if ($source->getTemplate())    { $newEmail->setTemplate($source->getTemplate()); }
        if ($source->getCustomHtml())  { $newEmail->setCustomHtml($source->getCustomHtml()); }
        if ($source->getFromName())    { $newEmail->setFromName($source->getFromName()); }
        if ($source->getFromAddress()) { $newEmail->setFromAddress($source->getFromAddress()); }
        $this->emailModel->saveEntity($newEmail);

        // Copy the exact MJML — no theme reload, preserves all slot content
        $sourceGjs = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $source]);
        if ($sourceGjs) {
            $newGjs = new GrapesJsBuilder();
            $newGjs->setEmail($newEmail);
            $newGjs->setCustomMjml($sourceGjs->getCustomMjml());
            $this->grapesJsBuilderModel->getRepository()->saveEntity($newGjs);
        }

        return [
            'success'  => true,
            'email'    => ['id' => $newEmail->getId(), 'name' => $newName],
            'locale'   => $locale,
            'language' => $language,
            'sourceId' => $sourceId,
            'message'  => "Email \"{$newName}\" (#{$newEmail->getId()}) created as {$language} copy of #{$sourceId}. Call get_email_components with this ID to retrieve the slots, then translate each with update_email_component.",
        ];
    }

    /**
     * Strips dangerous HTML from AI-generated email bodies.
     * Removes scripts, style blocks, event handlers, and javascript: hrefs
     * while preserving structural elements (headings, paragraphs, links, lists).
     */
    private function sanitizeEmailHtml(string $html): string
    {
        // Strip <script> and <style> tags with their contents
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $html);
        // Strip on* event handler attributes (e.g. onclick, onload, onerror)
        $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $html);
        // Replace javascript: href values with #
        $html = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', 'href="#"', $html);
        return $html;
    }

    /**
     * Wraps inner HTML body content in a minimal, responsive email HTML document.
     * Mautic token placeholders (e.g. {unsubscribe_text}) are passed through as-is.
     */
    private function wrapEmailBody(string $subject, string $body): string
    {
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeSubject}</title>
    <style type="text/css">
        body { margin:0; padding:0; background:#f4f4f4; -webkit-text-size-adjust:none; }
        table { border-collapse:collapse !important; }
        .wrapper { max-width:600px; margin:20px auto; background:#ffffff; border-radius:4px; }
        .content { padding:30px 40px; font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:1.6; color:#333333; }
        .content h1 { font-size:24px; color:#111111; margin:0 0 16px 0; }
        .content h2 { font-size:20px; color:#222222; margin:20px 0 10px 0; }
        .content h3 { font-size:16px; color:#333333; margin:16px 0 8px 0; }
        .content p  { margin:0 0 12px 0; }
        .content ul,.content ol { margin:0 0 12px 18px; padding:0; }
        .content li { margin:4px 0; }
        .content a  { color:#0078d4; text-decoration:none; }
        .footer { padding:16px 40px; background:#f8f8f8; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#888888; text-align:center; }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="wrapper">
                    <div class="content">{$body}</div>
                    <div class="footer">{unsubscribe_text}</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Reads a GrapesJS theme's MJML template, resolves asset URLs, injects the AI's body
     * content into the first substantial <mj-text> block, and returns the MJML string.
     * Returns null if the theme has no MJML template (e.g. legacy themes).
     */
    private function loadThemeMjml(string $template, string $body = ''): ?string
    {
        // Allowlist: only alphanumeric, dash, underscore — no path traversal
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $template)) {
            return null;
        }
        $themesDir = realpath(dirname(__DIR__, 3) . '/themes');
        if ($themesDir === false) {
            return null;
        }
        $themeDir = realpath($themesDir . '/' . $template . '/html');
        if ($themeDir === false || !str_starts_with($themeDir, $themesDir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        // GrapesJS themes use either email.mjml.twig or email.html.twig containing MJML
        foreach (['email.mjml.twig', 'email.html.twig'] as $filename) {
            $path = $themeDir . '/' . $filename;
            if (!file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);

            // Only proceed if this is actually MJML content
            if (!str_contains($content, '<mjml') && !str_contains($content, '<mj-body')) {
                continue;
            }

            // Resolve: 'themes/'~template~'/assets/foo.jpg'  →  /themes/THEME/assets/foo.jpg
            $content = str_replace("'~template~'", $template, $content);

            // Resolve: {{ getAssetUrl('themes/THEME/assets/foo.jpg', ...) }}  →  /themes/THEME/assets/foo.jpg
            // Leading slash makes URLs root-absolute so they resolve correctly in the browser.
            $content = preg_replace_callback(
                "/\{\{\s*getAssetUrl\s*\(\s*'([^']+)'[^}]*\)\s*\}\}/",
                fn($m) => '/' . ltrim($m[1], '/'),
                $content
            );

            // Strip any remaining Twig blocks/expressions
            $content = preg_replace('/\{%.*?%\}/s', '', $content);
            $content = preg_replace('/\{\{.*?\}\}/s', '', $content);

            // Inject AI body into the first <mj-text> block with substantial content (>80 plain chars)
            $body = $this->sanitizeEmailHtml($body);
            if (!empty($body)) {
                preg_match_all('/(<mj-text\b[^>]*>)([\s\S]*?)(<\/mj-text>)/i', $content, $m, PREG_OFFSET_CAPTURE);
                foreach ($m[2] as $idx => $match) {
                    if (strlen(strip_tags($match[0])) > 80) {
                        $fullMatch   = $m[0][$idx][0];
                        $offset      = $m[0][$idx][1];
                        $replacement = $m[1][$idx][0] . "\n" . $body . "\n" . $m[3][$idx][0];
                        $content     = substr($content, 0, $offset) . $replacement . substr($content, $offset + strlen($fullMatch));
                        break;
                    }
                }
            }

            return trim($content);
        }

        return null;
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
            $body = $this->sanitizeEmailHtml($params['body']);
            $email->setCustomHtml($this->wrapEmailBody($email->getSubject() ?? '', $body));

            // Also re-inject the body into the MJML template if one exists
            $gjs = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);
            if ($gjs) {
                $template = $email->getTemplate() ?? '';
                $newMjml  = $this->loadThemeMjml($template, $body);
                if ($newMjml !== null) {
                    $gjs->setCustomMjml($newMjml);
                    $this->grapesJsBuilderModel->getRepository()->saveEntity($gjs);
                }
            }
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

    private function getEmailComponents(array $args): array
    {
        $email = $this->emailModel->getEntity((int) $args['id']);
        if (!$email) {
            return ['success' => false, 'error' => "Email #{$args['id']} not found."];
        }

        $gjs = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);
        if (!$gjs || !$gjs->getCustomMjml()) {
            return ['success' => false, 'error' => "Email #{$args['id']} has no MJML content (was it created with a theme?)."];
        }

        preg_match_all(
            '/(<mj-text\b[^>]*>)([\s\S]*?)(<\/mj-text>)/i',
            $gjs->getCustomMjml(),
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $components = [];
        foreach ($matches[2] as $idx => $match) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($match[0])));
            $components[] = [
                'index'       => $idx,
                'currentText' => mb_substr($plain, 0, 200),
            ];
        }

        return ['success' => true, 'count' => count($components), 'components' => $components];
    }

    private function updateEmailComponent(array $args): array
    {
        $emailId = (int) $args['id'];
        $idx     = (int) $args['componentIndex'];
        $html    = $this->sanitizeEmailHtml($args['html'] ?? '');

        $email = $this->emailModel->getEntity($emailId);
        if (!$email) {
            return ['success' => false, 'error' => "Email #{$emailId} not found."];
        }

        $gjs = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);
        if (!$gjs || !$gjs->getCustomMjml()) {
            return ['success' => false, 'error' => "Email #{$emailId} has no MJML content."];
        }

        $mjml = $gjs->getCustomMjml();
        preg_match_all('/(<mj-text\b[^>]*>)([\s\S]*?)(<\/mj-text>)/i', $mjml, $m, PREG_OFFSET_CAPTURE);

        if (!isset($m[0][$idx])) {
            return ['success' => false, 'error' => "Component index {$idx} not found (email has " . count($m[0]) . " mj-text blocks)."];
        }

        $replacement = $m[1][$idx][0] . "\n" . $html . "\n" . $m[3][$idx][0];
        $newMjml     = substr($mjml, 0, $m[0][$idx][1])
                     . $replacement
                     . substr($mjml, $m[0][$idx][1] + strlen($m[0][$idx][0]));

        $gjs->setCustomMjml($newMjml);
        $this->grapesJsBuilderModel->getRepository()->saveEntity($gjs);

        return ['success' => true, 'message' => "Component #{$idx} of email #{$emailId} updated."];
    }

    private function getEmailImageComponents(array $args): array
    {
        $email = $this->emailModel->getEntity((int) $args['id']);
        if (!$email) {
            return ['success' => false, 'error' => "Email #{$args['id']} not found."];
        }

        $gjs = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);
        if (!$gjs || !$gjs->getCustomMjml()) {
            return ['success' => false, 'error' => "Email #{$args['id']} has no MJML content."];
        }

        // Match opening tag of mj-image in both formats:
        //   self-closing:     <mj-image src="…" />
        //   non-self-closing: <mj-image src="…">  (Mautic themes use this format)
        preg_match_all('/<mj-image\b[^>]*?>/i', $gjs->getCustomMjml(), $matches, PREG_OFFSET_CAPTURE);

        $images = [];
        foreach ($matches[0] as $idx => $match) {
            $tag = $match[0];
            $srcMatch = [];
            preg_match('/\bsrc=["\']([^"\']*)["\']/', $tag, $srcMatch);
            $images[] = [
                'index'      => $idx,
                'currentSrc' => $srcMatch[1] ?? '',
            ];
        }

        return ['success' => true, 'count' => count($images), 'images' => $images];
    }

    private function updateEmailImageComponent(array $args): array
    {
        $emailId  = (int) $args['id'];
        $idx      = (int) $args['imageIndex'];
        $imageUrl = trim($args['imageUrl'] ?? '');

        // Reject anything that isn't an HTTP/HTTPS URL or a root-relative path.
        // This prevents SVG inline strings, data URIs, or other garbage from
        // being stored as an image src (FILTER_SANITIZE_URL is too permissive).
        if (!preg_match('#^(https?://|/)#i', $imageUrl)) {
            return ['success' => false, 'error' => "Invalid imageUrl — must be an http(s) URL or a root-relative path (starts with /). Got: " . mb_substr($imageUrl, 0, 80)];
        }

        $email = $this->emailModel->getEntity($emailId);
        if (!$email) {
            return ['success' => false, 'error' => "Email #{$emailId} not found."];
        }

        $gjs = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);
        if (!$gjs || !$gjs->getCustomMjml()) {
            return ['success' => false, 'error' => "Email #{$emailId} has no MJML content."];
        }

        $mjml = $gjs->getCustomMjml();
        // Match opening tag only — handles both self-closing and non-self-closing formats
        preg_match_all('/<mj-image\b[^>]*?>/i', $mjml, $m, PREG_OFFSET_CAPTURE);

        if (!isset($m[0][$idx])) {
            return ['success' => false, 'error' => "Image index {$idx} not found (email has " . count($m[0]) . " mj-image blocks)."];
        }

        $tag    = $m[0][$idx][0];
        $offset = $m[0][$idx][1];

        // Replace existing src or inject it
        if (preg_match('/\bsrc=["\']/', $tag)) {
            $newTag = preg_replace('/\bsrc=["\'][^"\']*["\']/', 'src="' . $imageUrl . '"', $tag);
        } else {
            $newTag = preg_replace('/^<mj-image\b/i', '<mj-image src="' . $imageUrl . '"', $tag);
        }

        $newMjml = substr($mjml, 0, $offset) . $newTag . substr($mjml, $offset + strlen($tag));
        $gjs->setCustomMjml($newMjml);
        $this->grapesJsBuilderModel->getRepository()->saveEntity($gjs);

        return ['success' => true, 'message' => "Image slot #{$idx} of email #{$emailId} updated with new src."];
    }

    // ── Campaigns ─────────────────────────────────────────────────────────────

    private function listCampaigns(array $args): array
    {
        $limit  = min((int) ($args['limit'] ?? 20), 200);
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
        $limit  = min((int) ($args['limit'] ?? 20), 200);
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

    private function getSegment(array $args): array
    {
        $segment = $this->listModel->getEntity((int) $args['id']);
        if (!$segment) {
            return ['success' => false, 'error' => "Segment #{$args['id']} not found."];
        }

        return [
            'success' => true,
            'segment' => [
                'id'          => $segment->getId(),
                'name'        => $segment->getName(),
                'alias'       => $segment->getAlias(),
                'publicName'  => $segment->getPublicName(),
                'description' => $segment->getDescription(),
                'filters'     => $segment->getFilters(),
            ],
        ];
    }

    private function getSegmentFilterFields(): array
    {
        return [
            'success' => true,
            'fields'  => self::segmentFilterFieldList(),
        ];
    }

    private function createSegment(array $args): array
    {
        /** @var \Mautic\LeadBundle\Entity\LeadList $segment */
        $segment = $this->listModel->getEntity();
        $segment->setName($args['name']);

        if (!empty($args['alias'])) {
            $segment->setAlias($args['alias']);
        }
        if (isset($args['publicName'])) {
            $segment->setPublicName($args['publicName']);
        }
        if (isset($args['description'])) {
            $segment->setDescription($args['description']);
        }
        if (!empty($args['filters']) && is_array($args['filters'])) {
            $segment->setFilters($args['filters']);
        }

        $this->listModel->saveEntity($segment);

        return [
            'success' => true,
            'segment' => ['id' => $segment->getId(), 'name' => $segment->getName(), 'alias' => $segment->getAlias()],
            'message' => "Segment \"{$args['name']}\" created with ID #{$segment->getId()}.",
        ];
    }

    private function updateSegment(array $args): array
    {
        $segment = $this->listModel->getEntity((int) $args['id']);
        if (!$segment) {
            return ['success' => false, 'error' => "Segment #{$args['id']} not found."];
        }

        if (isset($args['name'])) {
            $segment->setName($args['name']);
        }
        if (isset($args['alias'])) {
            $segment->setAlias($args['alias']);
        }
        if (isset($args['publicName'])) {
            $segment->setPublicName($args['publicName']);
        }
        if (isset($args['description'])) {
            $segment->setDescription($args['description']);
        }
        if (isset($args['filters']) && is_array($args['filters'])) {
            $segment->setFilters($args['filters']);
        }

        $this->listModel->saveEntity($segment);

        return [
            'success' => true,
            'segment' => [
                'id'    => $segment->getId(),
                'name'  => $segment->getName(),
                'alias' => $segment->getAlias(),
            ],
            'message' => "Segment #{$segment->getId()} updated successfully.",
        ];
    }

    private static function segmentFilterFieldList(): array
    {
        $textOps   = ['=', '!=', 'like', '!like', 'contains', 'startsWith', 'endsWith', 'empty', '!empty', 'regexp', '!regexp'];
        $numOps    = ['=', '!=', 'gt', 'gte', 'lt', 'lte', 'empty', '!empty'];
        $dateOps   = ['=', '!=', 'gt', 'gte', 'lt', 'lte', 'between', '!between', 'empty', '!empty'];
        $selectOps = ['in', '!in', 'empty', '!empty'];

        return [
            // Standard text fields (object: lead)
            ['alias' => 'firstname',   'label' => 'First Name',    'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'lastname',    'label' => 'Last Name',     'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'email',       'label' => 'Email',         'type' => 'email',  'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'phone',       'label' => 'Phone',         'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'mobile',      'label' => 'Mobile',        'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'company',     'label' => 'Company',       'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'city',        'label' => 'City',          'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'state',       'label' => 'State',         'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'country',     'label' => 'Country',       'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'zipcode',     'label' => 'Zip Code',      'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'address1',    'label' => 'Address Line 1','type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'address2',    'label' => 'Address Line 2','type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'website',     'label' => 'Website',       'type' => 'url',    'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'title',       'label' => 'Title',         'type' => 'text',   'object' => 'lead', 'operators' => $textOps],
            ['alias' => 'industry',    'label' => 'Industry',      'type' => 'select', 'object' => 'lead', 'operators' => $selectOps],
            // Numeric
            ['alias' => 'points',      'label' => 'Lead Score',    'type' => 'number', 'object' => 'lead', 'operators' => $numOps],
            // Dates
            ['alias' => 'date_added',  'label' => 'Date Added',    'type' => 'date',   'object' => 'lead', 'operators' => $dateOps],
            ['alias' => 'last_active', 'label' => 'Last Active',   'type' => 'date',   'object' => 'lead', 'operators' => $dateOps],
            // Behavioral
            ['alias' => 'tags',        'label' => 'Tags',          'type' => 'tags',        'object' => 'lead', 'operators' => $selectOps],
            ['alias' => 'stage',       'label' => 'Stage',         'type' => 'stage',        'object' => 'lead', 'operators' => $selectOps],
            ['alias' => 'leadlist',    'label' => 'Segment membership', 'type' => 'leadlist', 'object' => 'lead', 'operators' => $selectOps],
            ['alias' => 'device_type', 'label' => 'Device Type',   'type' => 'select', 'object' => 'lead', 'operators' => $selectOps],
            // Do Not Contact (boolean: filter value 0=No, 1=Yes; operators = and !=)
            ['alias' => 'dnc_unsubscribed',  'label' => 'Do Not Contact: Email Unsubscribed', 'type' => 'boolean', 'object' => 'lead', 'operators' => ['=', '!=']],
            ['alias' => 'dnc_bounced',       'label' => 'Do Not Contact: Email Bounced',      'type' => 'boolean', 'object' => 'lead', 'operators' => ['=', '!=']],
            ['alias' => 'dnc_manual_email',  'label' => 'Do Not Contact: Email Manual',       'type' => 'boolean', 'object' => 'lead', 'operators' => ['=', '!=']],
            ['alias' => 'dnc_unsubscribed_sms', 'label' => 'Do Not Contact: SMS Unsubscribed','type' => 'boolean', 'object' => 'lead', 'operators' => ['=', '!=']],
            ['alias' => 'dnc_bounced_sms',   'label' => 'Do Not Contact: SMS Bounced',        'type' => 'boolean', 'object' => 'lead', 'operators' => ['=', '!=']],
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

    private function generateComplianceReport(array $args): array
    {
        $campaign = $this->campaignModel->getEntity((int) $args['campaign_id']);
        if (!$campaign) {
            return ['success' => false, 'error' => "Campaign #{$args['campaign_id']} not found."];
        }

        // Collect all email content from campaign events
        $emailsSummary = [];
        foreach ($campaign->getEvents() as $event) {
            if ($event->getType() === 'email.send') {
                $props   = $event->getProperties();
                $emailId = $props['email'] ?? null;
                if ($emailId) {
                    $email = $this->emailModel->getEntity((int) $emailId);
                    if ($email) {
                        $content         = strip_tags($email->getCustomHtml() ?: $email->getPlainText() ?: '');
                        $emailsSummary[] = [
                            'id'              => $email->getId(),
                            'name'            => $email->getName(),
                            'subject'         => $email->getSubject(),
                            'body_preview'    => mb_substr($content, 0, 800),
                            'has_unsubscribe' => stripos($content, 'unsubscribe') !== false,
                            'has_sender_info' => !empty($email->getFromAddress()),
                        ];
                    }
                }
            }
        }

        if (empty($emailsSummary)) {
            return ['success' => false, 'error' => 'No email assets found in this campaign to audit.'];
        }

        $campaignContext = json_encode([
            'campaign_name' => $campaign->getName(),
            'emails'        => $emailsSummary,
        ], JSON_PRETTY_PRINT);

        $prompt = "You are an EU AI Act and GDPR compliance auditor for marketing automation. "
            . "Audit the following campaign emails against these regulatory articles.\n\n"
            . "For each article, return pass/warning/fail based on the email content:\n"
            . "1. EU AI Act Art. 13 – Transparency: Are recipients informed AI is involved in targeting?\n"
            . "2. EU AI Act Art. 14 – Human Oversight: Is there evidence of human review in the process?\n"
            . "3. GDPR Art. 7 – Consent: Is there a clear unsubscribe / opt-out mechanism?\n"
            . "4. GDPR Art. 13 – Information: Is sender identity clearly disclosed?\n"
            . "5. GDPR Art. 22 – Automated Decisions: Are profiling or scoring decisions disclosed?\n"
            . "6. Dark Patterns: Are there urgency, scarcity, or guilt-tripping tactics?\n"
            . "7. Subject Line Honesty: Are subject lines accurate and non-deceptive?\n\n"
            . "Respond ONLY with valid JSON:\n"
            . '{"overall_compliance":"pass|warning|fail","compliance_rate":85,"articles":[{"article":"...","status":"pass|warning|fail","finding":"...","recommendation":"..."}],"critical_issues":["..."],"top_recommendations":["..."]}'
            . "\n\nCampaign data:\n" . $campaignContext;

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $report = json_decode($raw, true) ?? ['error' => 'Could not generate compliance report.'];

        return [
            'success'        => true,
            'campaign_name'  => $campaign->getName(),
            'emails_audited' => count($emailsSummary),
            'report'         => $report,
        ];
    }

    private function analyzeContactSentiment(array $args): array
    {
        $lead = $this->leadModel->getEntity((int) $args['contact_id']);
        if (!$lead) {
            return ['success' => false, 'error' => "Contact #{$args['contact_id']} not found."];
        }

        $fields = $lead->getProfileFields();

        // Collect meaningful text fields from the contact profile
        $textData        = [];
        $textFieldNames  = ['notes', 'comments', 'about', 'description', 'message', 'feedback', 'title', 'jobtitle', 'job_title', 'interests', 'industry'];
        foreach ($fields as $key => $value) {
            if (!empty($value) && is_string($value) && mb_strlen($value) > 3) {
                if (in_array(strtolower($key), $textFieldNames, true) || mb_strlen($value) > 20) {
                    $textData[$key] = mb_substr((string) $value, 0, 200);
                }
            }
        }

        $lastActive       = $lead->getLastActive();
        $daysSinceActive  = $lastActive ? (int) (new \DateTime())->diff($lastActive)->days : null;

        $contactSummary = [
            'name'              => trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? '')),
            'company'           => $fields['company'] ?? '',
            'lead_score'        => $lead->getPoints(),
            'days_since_active' => $daysSinceActive,
            'text_fields'       => $textData,
        ];

        if (empty($textData) && $lead->getPoints() === 0 && $daysSinceActive === null) {
            return ['success' => false, 'error' => 'Contact has insufficient profile data or activity to analyze.'];
        }

        $prompt = "You are a CRM sentiment analysis expert. Analyze this marketing contact's profile data and infer their sentiment and engagement signals.\n\n"
            . "Contact data:\n" . json_encode($contactSummary, JSON_PRETTY_PRINT) . "\n\n"
            . "Based on available data (text fields, lead score, activity recency), determine:\n"
            . "- Overall sentiment toward the brand/product\n"
            . "- Key signals driving that sentiment\n"
            . "- Visible topics or interests\n"
            . "- Recommended next action\n\n"
            . "Respond ONLY with valid JSON:\n"
            . '{"sentiment":"positive|neutral|negative","confidence":0.8,"sentiment_score":72,"key_signals":["..."],"topics":["..."],"engagement_level":"high|medium|low|dormant","recommended_action":"..."}'
            . "\n\nconfidence is 0–1, sentiment_score is 0–100 (0=very negative, 50=neutral, 100=very positive).";

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $analysis = json_decode($raw, true) ?? ['error' => 'Could not parse sentiment analysis.'];

        return [
            'success'      => true,
            'contact_name' => $contactSummary['name'],
            'contact_id'   => $lead->getId(),
            'analysis'     => $analysis,
        ];
    }

    private function scoreContactHealth(array $args): array
    {
        $lead = $this->leadModel->getEntity((int) $args['contact_id']);
        if (!$lead) {
            return ['success' => false, 'error' => "Contact #{$args['contact_id']} not found."];
        }

        $fields         = $lead->getProfileFields();
        $now            = new \DateTime();
        $lastActive     = $lead->getLastActive();
        $dateAdded      = $lead->getDateAdded();
        $daysSinceActive = $lastActive ? (int) $now->diff($lastActive)->days : null;
        $daysSinceAdded  = $dateAdded  ? (int) $now->diff($dateAdded)->days  : null;

        // Count segment memberships
        $segmentCount = 0;
        try {
            $lists        = $lead->getLists();
            $segmentCount = $lists ? count($lists) : 0;
        } catch (\Throwable) {}

        $contactData = [
            'name'               => trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? '')),
            'email'              => $fields['email'] ?? '',
            'lead_score'         => $lead->getPoints(),
            'days_since_active'  => $daysSinceActive,
            'days_since_created' => $daysSinceAdded,
            'segment_count'      => $segmentCount,
            'is_unsubscribed'    => (bool) ($fields['unsubscribed'] ?? false),
        ];

        $prompt = "You are a customer health scoring expert for marketing automation. "
            . "Score this contact's engagement health based on the provided signals.\n\n"
            . "Contact data:\n" . json_encode($contactData, JSON_PRETTY_PRINT) . "\n\n"
            . "Scoring guidelines:\n"
            . "- healthy (80-100): Active recently (<14 days), strong lead score, multiple segments\n"
            . "- moderate (50-79): Some activity, moderate score, occasional engagement\n"
            . "- at_risk (25-49): Inactive 30-90 days, low or declining score\n"
            . "- churning (0-24): Inactive >90 days, near-zero score, unsubscribed or silent\n\n"
            . "Respond ONLY with valid JSON:\n"
            . '{"health_score":72,"risk_level":"healthy|moderate|at_risk|churning","explanation":"...","strengths":["..."],"concerns":["..."],"recommended_action":"..."}'
            . "\n\nhealth_score is 0-100. Be concise and data-driven.";

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $scoreData = json_decode($raw, true) ?? ['error' => 'Could not generate health score.'];

        return [
            'success'      => true,
            'contact_name' => $contactData['name'],
            'contact_id'   => $lead->getId(),
            'score_data'   => $scoreData,
        ];
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    private function listAssets(array $args): array
    {
        $assetModel = $this->modelFactory->getModel('asset');
        $limit      = (int) ($args['limit'] ?? 20);
        $search     = $args['search'] ?? '';

        $assets = $assetModel->getEntities([
            'start'  => 0,
            'limit'  => $limit,
            'filter' => ['string' => $search],
        ]);

        $data = [];
        foreach ($assets as $asset) {
            $data[] = [
                'id'          => $asset->getId(),
                'title'       => $asset->getTitle(),
                'description' => $asset->getDescription(),
                'language'    => $asset->getLanguage(),
                'extension'   => $asset->getExtension(),
                'mime'        => $asset->getMime(),
                'category'    => $asset->getCategory()?->getTitle(),
                'url'         => $assetModel->generateUrl($asset, true),
            ];
        }

        return ['success' => true, 'assets' => $data, 'count' => count($data)];
    }

    private function getAsset(array $args): array
    {
        $assetModel = $this->modelFactory->getModel('asset');
        $asset      = $assetModel->getEntity((int) $args['id']);

        if (!$asset) {
            return ['success' => false, 'error' => "Asset #{$args['id']} not found."];
        }

        return [
            'success' => true,
            'asset'   => [
                'id'              => $asset->getId(),
                'title'           => $asset->getTitle(),
                'description'     => $asset->getDescription(),
                'language'        => $asset->getLanguage(),
                'extension'       => $asset->getExtension(),
                'mime'            => $asset->getMime(),
                'storageLocation' => $asset->getStorageLocation(),
                'disallow'        => $asset->getDisallow(),
                'category'        => $asset->getCategory()?->getTitle(),
                'category_id'     => $asset->getCategory()?->getId(),
                'url'             => $assetModel->generateUrl($asset, true),
            ],
        ];
    }

    private function listAssetCategories(): array
    {
        $categoryModel = $this->modelFactory->getModel('category');
        $categories    = $categoryModel->getRepository()->getCategoryList('asset', '', 50, 0);

        return ['success' => true, 'categories' => $categories];
    }

    private function createAssetCategory(array $args): array
    {
        $categoryModel = $this->modelFactory->getModel('category');
        $category      = $categoryModel->getEntity();
        $category->setTitle($args['title']);
        $category->setBundle('asset');
        $category->setIsPublished(true);

        if (!empty($args['description'])) {
            $category->setDescription($args['description']);
        }

        $categoryModel->saveEntity($category);

        return [
            'success'  => true,
            'category' => ['id' => $category->getId(), 'title' => $category->getTitle()],
            'message'  => "Asset category \"{$args['title']}\" created with ID #{$category->getId()}.",
        ];
    }

    private function generateImageAsset(array $args): array
    {
        // 1. Generate image via Gemini
        $imageResult = $this->geminiClient->generateImage($args['prompt']);
        $mimeType    = $imageResult['mimeType'];  // e.g. 'image/png'
        $imageData   = base64_decode($imageResult['data']);

        // Derive extension from mime type
        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'png',
        };

        // 2. Save to Mautic media/files directory
        $uploadDir = (string) $this->parametersHelper->get('upload_dir');
        $filename  = 'ai_' . uniqid('', true) . '.' . $ext;
        $filePath  = $uploadDir . '/' . $filename;

        if (file_put_contents($filePath, $imageData) === false) {
            return ['success' => false, 'error' => "Failed to write image to disk at {$filePath}."];
        }

        // 3. Create Mautic asset entity
        $assetModel = $this->modelFactory->getModel('asset');
        $asset      = $assetModel->getEntity();
        $asset->setTitle($args['title']);
        $asset->setStorageLocation('local');
        $asset->setUploadDir($uploadDir);
        $asset->setPath($filename);
        $asset->setFileInfoFromFile();   // reads mime, extension, size from the file on disk
        $asset->setLanguage($args['language'] ?? 'en');
        $asset->setDisallow(false);      // allow direct public access (required for email image rendering)
        $asset->setIsPublished(true);    // available for use = checked

        if (!empty($args['description'])) {
            $asset->setDescription($args['description']);
        }

        if (!empty($args['category_id'])) {
            $categoryModel = $this->modelFactory->getModel('category');
            $category      = $categoryModel->getEntity((int) $args['category_id']);
            if ($category) {
                $asset->setCategory($category);
            }
        }

        $assetModel->saveEntity($asset);

        // Use Mautic's own asset download route (/asset/{id}:{alias}) — the media/files/
        // directory is blocked by .htaccess deny from all, so direct file URLs 403.
        $publicUrl = $assetModel->generateUrl($asset, true);

        return [
            'success' => true,
            'asset'   => [
                'id'       => $asset->getId(),
                'title'    => $asset->getTitle(),
                'filename' => $filename,
                'url'      => $publicUrl,
                'mime'     => $asset->getMime(),
            ],
            'message' => "Image asset \"{$args['title']}\" created with ID #{$asset->getId()} and saved as {$filename}. Public URL: {$publicUrl}",
        ];
    }

    private function updateAsset(array $args): array
    {
        $assetModel = $this->modelFactory->getModel('asset');
        $asset      = $assetModel->getEntity((int) $args['id']);

        if (!$asset) {
            return ['success' => false, 'error' => "Asset #{$args['id']} not found."];
        }

        if (isset($args['title']))       { $asset->setTitle($args['title']); }
        if (isset($args['description'])) { $asset->setDescription($args['description']); }
        if (isset($args['language']))    { $asset->setLanguage($args['language']); }
        if (isset($args['disallow']))    { $asset->setDisallow((bool) $args['disallow']); }

        if (!empty($args['category_id'])) {
            $categoryModel = $this->modelFactory->getModel('category');
            $category      = $categoryModel->getEntity((int) $args['category_id']);
            if ($category) {
                $asset->setCategory($category);
            }
        }

        $assetModel->saveEntity($asset);

        return [
            'success' => true,
            'asset'   => ['id' => $asset->getId(), 'title' => $asset->getTitle()],
            'message' => "Asset #{$args['id']} updated.",
        ];
    }

    // ── Landing Pages ─────────────────────────────────────────────────────────

    private function listPageThemes(): array
    {
        $themesDir = dirname(__DIR__, 3) . '/themes';
        if (!is_dir($themesDir)) {
            return ['success' => false, 'error' => 'Themes directory not found at: ' . $themesDir];
        }

        $themes = [];
        foreach (glob($themesDir . '/*/config.json') ?: [] as $configFile) {
            $config   = json_decode(file_get_contents($configFile), true) ?? [];
            $features = $config['features'] ?? [];
            $builders = $config['builder']  ?? [];

            if (!in_array('page', $features, true)) {
                continue;
            }
            if (!in_array('grapesjsbuilder', $builders, true)) {
                continue;
            }

            $themes[] = [
                'name'  => basename(dirname($configFile)),
                'label' => $config['name'] ?? basename(dirname($configFile)),
            ];
        }

        return ['success' => true, 'themes' => $themes, 'count' => count($themes)];
    }

    private function listPages(array $args): array
    {
        $results = $this->pageModel->getEntities([
            'filter'     => ['string' => $args['search'] ?? ''],
            'limit'      => min((int) ($args['limit'] ?? 20), 100),
            'start'      => 0,
            'orderBy'    => 'p.title',
            'orderByDir' => 'asc',
        ]);

        $pages = [];
        foreach ($results as $page) {
            $pages[] = [
                'id'          => $page->getId(),
                'title'       => $page->getTitle(),
                'alias'       => $page->getAlias(),
                'isPublished' => $page->isPublished(),
                'template'    => $page->getTemplate(),
                'publicUrl'   => '/p/' . $page->getAlias(),
            ];
        }

        return ['success' => true, 'pages' => $pages, 'count' => count($pages)];
    }

    private function getPage(array $args): array
    {
        $page = $this->pageModel->getEntity((int) $args['id']);
        if (!$page) {
            return ['success' => false, 'error' => "Page #{$args['id']} not found."];
        }

        return ['success' => true, 'page' => [
            'id'              => $page->getId(),
            'title'           => $page->getTitle(),
            'alias'           => $page->getAlias(),
            'template'        => $page->getTemplate(),
            'customHtml'      => mb_substr($page->getCustomHtml() ?? '', 0, 2000),
            'metaDescription' => $page->getMetaDescription(),
            'isPublished'     => $page->isPublished(),
            'hits'            => $page->getHits(),
            'publicUrl'       => '/p/' . $page->getAlias(),
        ]];
    }

    private function createPage(array $args): array
    {
        /** @var \Mautic\PageBundle\Entity\Page $page */
        $page = $this->pageModel->getEntity();
        $page->setTitle($args['title']);

        $alias = trim($args['alias'] ?? '');
        if ($alias === '') {
            $alias = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $args['title']));
            $alias = trim($alias, '-');
        }
        $page->setAlias($alias);

        if (!empty($args['template'])) {
            $page->setTemplate($args['template']);
        }

        $page->setCustomHtml($args['content'] ?? '');

        if (!empty($args['metaDescription'])) {
            $page->setMetaDescription($args['metaDescription']);
        }

        $page->setIsPublished($args['isPublished'] ?? true);

        $this->pageModel->saveEntity($page);

        return [
            'success' => true,
            'page'    => [
                'id'        => $page->getId(),
                'title'     => $page->getTitle(),
                'alias'     => $page->getAlias(),
                'publicUrl' => '/p/' . $page->getAlias(),
            ],
            'message' => "Landing page \"{$page->getTitle()}\" created (ID #{$page->getId()}). "
                . "Public URL: /p/{$page->getAlias()}.",
        ];
    }

    private function updatePage(array $args): array
    {
        $page = $this->pageModel->getEntity((int) $args['id']);
        if (!$page) {
            return ['success' => false, 'error' => "Page #{$args['id']} not found."];
        }

        $p = $args['params'] ?? [];
        if (isset($p['title']))           { $page->setTitle($p['title']); }
        if (isset($p['content']))         { $page->setCustomHtml($p['content']); }
        if (isset($p['template']))        { $page->setTemplate($p['template']); }
        if (isset($p['alias']))           { $page->setAlias($p['alias']); }
        if (isset($p['metaDescription'])) { $page->setMetaDescription($p['metaDescription']); }
        if (isset($p['isPublished']))     { $page->setIsPublished((bool) $p['isPublished']); }

        $this->pageModel->saveEntity($page);

        return [
            'success' => true,
            'message' => "Page #{$args['id']} updated.",
            'page'    => ['id' => $page->getId(), 'title' => $page->getTitle()],
        ];
    }

    // ── Forms ─────────────────────────────────────────────────────────────────

    private function listForms(array $args): array
    {
        $results = $this->formModel->getEntities([
            'filter'     => ['string' => $args['search'] ?? ''],
            'limit'      => min((int) ($args['limit'] ?? 20), 100),
            'start'      => 0,
            'orderBy'    => 'f.name',
            'orderByDir' => 'asc',
        ]);

        $forms = [];
        foreach ($results as $form) {
            $forms[] = [
                'id'          => $form->getId(),
                'name'        => $form->getName(),
                'alias'       => $form->getAlias(),
                'formType'    => $form->getFormType(),
                'isPublished' => $form->isPublished(),
                'fields'      => $form->getFields()->count(),
            ];
        }

        return ['success' => true, 'forms' => $forms, 'count' => count($forms)];
    }

    private function getForm(array $args): array
    {
        $form = $this->formModel->getEntity((int) $args['id']);
        if (!$form) {
            return ['success' => false, 'error' => "Form #{$args['id']} not found."];
        }

        $fields = [];
        foreach ($form->getFields() as $field) {
            $fields[] = [
                'id'           => $field->getId(),
                'label'        => $field->getLabel(),
                'alias'        => $field->getAlias(),
                'type'         => $field->getType(),
                'isRequired'   => $field->getIsRequired(),
                'order'        => $field->getOrder(),
                'mappedObject' => $field->getMappedObject(),
                'mappedField'  => $field->getMappedField(),
            ];
        }

        $actions = [];
        foreach ($form->getActions() as $action) {
            $actions[] = [
                'id'         => $action->getId(),
                'name'       => $action->getName(),
                'type'       => $action->getType(),
                'properties' => $action->getProperties(),
            ];
        }

        return ['success' => true, 'form' => [
            'id'                 => $form->getId(),
            'name'               => $form->getName(),
            'alias'              => $form->getAlias(),
            'formType'           => $form->getFormType(),
            'postAction'         => $form->getPostAction(),
            'postActionProperty' => $form->getPostActionProperty(),
            'isPublished'        => $form->isPublished(),
            'fields'             => $fields,
            'actions'            => $actions,
            'embedUrl'           => '/form/' . $form->getId(),
        ]];
    }

    private function createForm(array $args): array
    {
        /** @var \Mautic\FormBundle\Entity\Form $form */
        $form = $this->formModel->getEntity();
        $form->setName($args['name']);

        if (!empty($args['description'])) {
            $form->setDescription($args['description']);
        }
        $form->setFormType($args['formType'] ?? 'standalone');
        $form->setPostAction($args['postAction'] ?? 'message');
        $form->setPostActionProperty(
            $args['postActionProperty'] ?? 'Thank you! Your response has been recorded.'
        );
        $form->setIsPublished($args['isPublished'] ?? true);
        $form->setRenderStyle(false);
        $form->setInKioskMode(false);

        // ── Build session-format field array ──────────────────────────────────
        $sessionFields = [];
        $order         = 1;
        $hasButton     = false;

        foreach ($args['fields'] as $i => $def) {
            $type = $def['type'] ?? 'text';
            if ($type === 'button') {
                $hasButton = true;
            }

            $alias = $def['alias'] ?? '';
            if ($alias === '') {
                $alias = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $def['label'] ?? 'field'));
                $alias = trim($alias, '_');
            }

            $key                 = 'new_' . ($i + 1);
            $sessionFields[$key] = [
                'id'                  => null,
                'label'               => $def['label'] ?? 'Field',
                'alias'               => $alias,
                'type'                => $type,
                'isRequired'          => (bool) ($def['isRequired'] ?? false),
                'defaultValue'        => $def['defaultValue'] ?? '',
                'helpMessage'         => $def['helpMessage'] ?? '',
                'validationMessage'   => $def['validationMessage'] ?? '',
                'showLabel'           => true,
                'order'               => (int) ($def['order'] ?? $order),
                'properties'          => $def['properties'] ?? [],
                'validation'          => [],
                'conditions'          => null,
                'labelAttributes'     => null,
                'inputAttributes'     => null,
                'containerAttributes' => null,
                'saveResult'          => $type !== 'button',
                'isAutoFill'          => (bool) ($def['isAutoFill'] ?? false),
                'mappedObject'        => $def['mappedObject'] ?? null,
                'mappedField'         => $def['mappedField'] ?? null,
            ];
            $order++;
        }

        // Always ensure a submit button exists
        if (!$hasButton) {
            $sessionFields['new_submit'] = [
                'id'                  => null,
                'label'               => 'Submit',
                'alias'               => 'submit',
                'type'                => 'button',
                'isRequired'          => false,
                'defaultValue'        => '',
                'helpMessage'         => '',
                'validationMessage'   => '',
                'showLabel'           => true,
                'order'               => 999,
                'properties'          => [],
                'validation'          => [],
                'conditions'          => null,
                'labelAttributes'     => null,
                'inputAttributes'     => null,
                'containerAttributes' => null,
                'saveResult'          => false,
                'isAutoFill'          => false,
                'mappedObject'        => null,
                'mappedField'         => null,
            ];
        }

        $this->formModel->setFields($form, $sessionFields);

        // ── Build session-format action array ─────────────────────────────────
        if (!empty($args['actions'])) {
            $sessionActions = [];
            foreach ($args['actions'] as $i => $actionDef) {
                $sessionActions['new_a' . ($i + 1)] = [
                    'id'          => null,
                    'name'        => $actionDef['name'] ?? $actionDef['type'],
                    'description' => $actionDef['description'] ?? '',
                    'type'        => $actionDef['type'],
                    'order'       => (int) ($actionDef['order'] ?? ($i + 1)),
                    'properties'  => $actionDef['properties'] ?? [],
                ];
            }
            $this->formModel->setActions($form, $sessionActions);
        }

        $this->formModel->saveEntity($form);

        return [
            'success' => true,
            'form'    => [
                'id'       => $form->getId(),
                'name'     => $form->getName(),
                'alias'    => $form->getAlias(),
                'embedUrl' => '/form/' . $form->getId(),
            ],
            'message' => "Form \"{$form->getName()}\" created (ID #{$form->getId()}). "
                . "Embed URL: /form/{$form->getId()}.",
        ];
    }

    private function updateForm(array $args): array
    {
        $form = $this->formModel->getEntity((int) $args['id']);
        if (!$form) {
            return ['success' => false, 'error' => "Form #{$args['id']} not found."];
        }

        $p = $args['params'] ?? [];
        if (isset($p['name']))               { $form->setName($p['name']); }
        if (isset($p['description']))        { $form->setDescription($p['description']); }
        if (isset($p['postAction']))         { $form->setPostAction($p['postAction']); }
        if (isset($p['postActionProperty'])) { $form->setPostActionProperty($p['postActionProperty']); }
        if (isset($p['isPublished']))        { $form->setIsPublished((bool) $p['isPublished']); }

        $this->formModel->saveEntity($form);

        return [
            'success' => true,
            'message' => "Form #{$args['id']} updated.",
            'form'    => ['id' => $form->getId(), 'name' => $form->getName()],
        ];
    }

    // ── Voice of Customer (VoC) Analytics ──────────────────────────────────────

    private function vocCollectFeedback(array $args): array
    {
        $data = $this->vocEngine->collectFeedback(
            source:   $args['source'] ?? 'all',
            formIds:  $args['form_ids'] ?? [],
            dateFrom: $args['date_from'] ?? null,
            dateTo:   $args['date_to'] ?? null,
            limit:    (int) ($args['limit'] ?? 200),
        );

        return [
            'success'         => true,
            'verbatim_count'  => $data['total_count'],
            'sources_queried' => $data['sources_queried'],
            'contact_ids'     => $data['contact_ids'],
            'verbatims'       => array_slice($data['verbatims'], 0, 50), // cap SSE payload
            'message'         => sprintf(
                'Collected %d PII-redacted verbatims from %s.',
                $data['total_count'],
                implode(', ', $data['sources_queried'])
            ),
        ];
    }

    private function vocAnalyzeThemes(array $args): array
    {
        // Step 1: collect verbatims
        $data = $this->vocEngine->collectFeedback(
            source:   $args['source'] ?? 'all',
            formIds:  $args['form_ids'] ?? [],
            dateFrom: $args['date_from'] ?? null,
            dateTo:   $args['date_to'] ?? null,
            limit:    200,
        );

        if (empty($data['verbatims'])) {
            return ['success' => false, 'error' => 'No verbatims found for the given criteria. Try broadening date range or sources.'];
        }

        // Step 2: AI theme analysis
        $themes = $this->vocEngine->analyzeThemes($data['verbatims']);

        return [
            'success'         => true,
            'verbatim_count'  => $data['total_count'],
            'sources_queried' => $data['sources_queried'],
            'contact_ids'     => $data['contact_ids'],
            'themes'          => $themes,
            'theme_count'     => count($themes),
            'message'         => sprintf(
                'Discovered %d themes from %d verbatims across %s.',
                count($themes),
                $data['total_count'],
                implode(', ', $data['sources_queried'])
            ),
        ];
    }

    private function vocContactVoice(array $args): array
    {
        $contactId = (int) $args['contact_id'];
        $lead = $this->leadModel->getEntity($contactId);
        if (!$lead) {
            return ['success' => false, 'error' => "Contact #{$contactId} not found."];
        }

        $profile = $this->vocEngine->analyzeContactVoice($contactId);

        $fields = $lead->getProfileFields();
        $name   = trim(($fields['firstname'] ?? '') . ' ' . ($fields['lastname'] ?? ''));

        return [
            'success'      => true,
            'contact_id'   => $contactId,
            'contact_name' => $name ?: "Contact #{$contactId}",
            'voc_profile'  => $profile,
            'message'      => sprintf(
                'VoC profile for %s: %s sentiment, %d topics identified.',
                $name ?: "#{$contactId}",
                $profile['sentiment'] ?? 'unknown',
                count($profile['topics'] ?? [])
            ),
        ];
    }

    private function vocSummarizeTheme(array $args): array
    {
        $themeName = $args['theme_name'];

        // Collect verbatims to filter by theme
        $data = $this->vocEngine->collectFeedback(
            source:   $args['source'] ?? 'all',
            formIds:  $args['form_ids'] ?? [],
            dateFrom: null,
            dateTo:   null,
            limit:    200,
        );

        if (empty($data['verbatims'])) {
            return ['success' => false, 'error' => 'No verbatims found to summarize this theme.'];
        }

        $summary = $this->vocEngine->summarizeTheme($themeName, $data['verbatims']);

        return [
            'success'      => true,
            'theme_name'   => $themeName,
            'contact_ids'  => $data['contact_ids'],
            'summary'      => $summary,
            'message'      => sprintf(
                'Theme "%s": %s severity — %s',
                $themeName,
                $summary['severity'] ?? 'unknown',
                $summary['summary'] ?? 'see details'
            ),
        ];
    }

    private function vocCreateInsightSegment(array $args): array
    {
        $result = $this->vocEngine->createInsightSegment(
            name:        $args['name'],
            description: $args['description'] ?? '',
            contactIds:  $args['contact_ids'],
        );

        return [
            'success'    => true,
            'segment'    => $result,
            'message'    => sprintf(
                'Created segment "%s" (ID #%d) with %d contacts. View at /s/segments/view/%d',
                $args['name'],
                $result['id'],
                $result['contact_count'],
                $result['id']
            ),
        ];
    }

    private function vocSuggestResponseCampaign(array $args): array
    {
        $theme     = $args['theme'];
        $sentiment = $args['sentiment'];
        $audience  = $args['audience'] ?? '';
        $context   = $args['context'] ?? '';

        $prompt = "You are a marketing automation strategist. Based on a Voice of Customer (VoC) insight, "
            . "design a response campaign to address the identified theme.\n\n"
            . "Theme: {$theme}\n"
            . "Sentiment: {$sentiment}\n"
            . ($audience ? "Target audience: {$audience}\n" : '')
            . ($context ? "Additional context: {$context}\n" : '')
            . "\nDesign a 3-5 email journey that addresses this theme. For each email:\n"
            . "- Subject line\n- Timing (delay from previous)\n- Purpose/goal\n- Key messaging points\n- CTA\n\n"
            . "Also provide:\n- Overall campaign name\n- Campaign goal\n- Recommended segment criteria\n- Success metrics\n\n"
            . "Respond ONLY with valid JSON:\n"
            . '{"campaign_name":"...","campaign_goal":"...","segment_criteria":"...","success_metrics":["..."],'
            . '"emails":[{"step":1,"subject":"...","delay":"immediate|3 days|...","purpose":"...","key_messages":["..."],"cta":"..."}],'
            . '"additional_recommendations":["..."]}';

        $response = $this->mistralClient->complete([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $raw = $response['content'] ?? '{}';
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $campaign = json_decode($raw, true) ?? ['error' => 'Could not generate campaign plan.'];

        return [
            'success'       => true,
            'theme'         => $theme,
            'sentiment'     => $sentiment,
            'campaign_plan' => $campaign,
            'message'       => sprintf(
                'Response campaign "%s" planned with %d emails for theme "%s".',
                $campaign['campaign_name'] ?? 'Campaign',
                count($campaign['emails'] ?? []),
                $theme
            ),
        ];
    }

    // ── Survey Templates & Analytics ──────────────────────────────────────

    /**
     * List available VoC survey templates.
     */
    private function listSurveyTemplates(): array
    {
        return [
            'success'   => true,
            'templates' => SurveyTemplates::listTemplates(),
            'message'   => 'Available survey templates: NPS, CSAT, CES, Product-Market Fit, Onboarding, Churn/Exit, Post-Purchase.',
        ];
    }

    /**
     * Create a VoC survey from a pre-built template.
     */
    private function createSurvey(array $args): array
    {
        $template = $args['template'] ?? '';

        try {
            $formArgs = SurveyTemplates::getTemplate(
                template:       $template,
                companyName:    $args['company_name']     ?? null,
                productName:    $args['product_name']     ?? null,
                customFollowUp: $args['custom_follow_up'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        // Delegate to existing form creation
        $result = $this->createForm($formArgs);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $scoring = SurveyTemplates::getScoringConfig($template);
        $formId  = $result['form']['id'] ?? 0;

        $result['survey_type'] = $template;
        $result['scoring']     = $scoring;
        $result['message']     = sprintf(
            '%s survey "%s" created (ID #%d). Scoring: %s. Embed: /form/%d — use survey_analytics with form_id=%d to compute results after responses are collected.',
            strtoupper($template),
            $result['form']['name'] ?? 'Survey',
            $formId,
            $scoring['formula'] ?? $scoring['method'],
            $formId,
            $formId,
        );

        return $result;
    }

    /**
     * Compute survey metrics from form responses.
     */
    private function surveyAnalytics(array $args): array
    {
        $formId = (int) ($args['form_id'] ?? 0);
        if ($formId <= 0) {
            return ['success' => false, 'error' => 'form_id is required.'];
        }

        $form = $this->formModel->getEntity($formId);
        if (!$form) {
            return ['success' => false, 'error' => "Form #{$formId} not found."];
        }

        // Detect survey type from field aliases
        $fieldAliases = [];
        foreach ($form->getFields() as $field) {
            $fieldAliases[] = $field->getAlias();
        }

        $surveyType = $this->detectSurveyType($fieldAliases);
        if (!$surveyType) {
            return [
                'success' => false,
                'error'   => 'Could not detect survey type from field aliases. Ensure the form was created with create_survey.',
            ];
        }

        $scoring = SurveyTemplates::getScoringConfig($surveyType);

        // Collect submissions
        $submissionModel = $this->modelFactory->getModel('form.submission');
        try {
            $submissions = $submissionModel->getRepository()->getEntities([
                'form'  => $form,
                'limit' => 1000,
                'start' => 0,
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Failed to load submissions: ' . $e->getMessage()];
        }

        // Determine score field(s)
        $scoreFields = isset($scoring['score_fields'])
            ? $scoring['score_fields']
            : [$scoring['score_field']];

        $scores     = [];
        $verbatims  = [];
        $contactIds = [];

        foreach ($submissions as $submission) {
            $results       = $submission->getResults();
            $dateSubmitted = $submission->getDateSubmitted();

            // Date filtering
            if (!empty($args['date_from'])) {
                try {
                    if ($dateSubmitted < new \DateTime($args['date_from'])) {
                        continue;
                    }
                } catch (\Throwable) {}
            }
            if (!empty($args['date_to'])) {
                try {
                    if ($dateSubmitted > new \DateTime($args['date_to'])) {
                        continue;
                    }
                } catch (\Throwable) {}
            }

            // Collect score(s)
            if (isset($scoring['score_fields'])) {
                // Multi-field (post_purchase)
                $row = [];
                foreach ($scoring['score_fields'] as $sf) {
                    $row[$sf] = $results[$sf] ?? null;
                }
                $scores[] = $row;
            } else {
                $scores[] = $results[$scoring['score_field']] ?? null;
            }

            // Collect text verbatims from follow-up fields
            foreach ($results as $alias => $val) {
                if (!in_array($alias, $scoreFields, true)
                    && is_string($val)
                    && mb_strlen(trim($val)) > 3
                    && !is_numeric($val)
                ) {
                    $verbatims[] = $val;
                }
            }

            $cid = $submission->getLead()?->getId();
            if ($cid) {
                $contactIds[] = $cid;
            }
        }

        // Compute metric
        $metric = $this->calculateSurveyMetric($surveyType, $scores, $scoring);

        // AI interpretation
        $interpretation = $this->interpretSurveyResults($surveyType, $metric, $verbatims);

        return [
            'success'         => true,
            'form_id'         => $formId,
            'form_name'       => $form->getName(),
            'survey_type'     => $surveyType,
            'response_count'  => count($scores),
            'metric'          => $metric,
            'verbatim_count'  => count($verbatims),
            'contact_ids'     => array_values(array_unique($contactIds)),
            'interpretation'  => $interpretation,
            'message'         => sprintf(
                '%s survey "%s": %s (based on %d responses)',
                strtoupper($surveyType),
                $form->getName(),
                $metric['summary'] ?? 'See details',
                count($scores),
            ),
        ];
    }

    /**
     * Detect survey type from field aliases.
     */
    private function detectSurveyType(array $aliases): ?string
    {
        $aliasSet = array_flip($aliases);

        if (isset($aliasSet['nps_score']))              return 'nps';
        if (isset($aliasSet['csat_score']))             return 'csat';
        if (isset($aliasSet['ces_score']))              return 'ces';
        if (isset($aliasSet['pmf_score']))              return 'pmf';
        if (isset($aliasSet['onboarding_rating']))      return 'onboarding';
        if (isset($aliasSet['churn_reason']))           return 'churn';
        if (isset($aliasSet['purchase_satisfaction']))   return 'post_purchase';

        return null;
    }

    /**
     * Calculate the survey metric based on scoring method.
     */
    private function calculateSurveyMetric(string $type, array $scores, array $scoring): array
    {
        $valid = array_filter($scores, fn($s) => $s !== null);
        $count = count($valid);

        if ($count === 0) {
            return ['score' => null, 'summary' => 'No responses yet', 'breakdown' => []];
        }

        return match ($scoring['method']) {
            'nps'                       => $this->calculateNps($valid),
            'top_box_percentage'        => $this->calculateTopBox($valid, $scoring['top_box']),
            'average'                   => $this->calculateAverage($valid, $scoring['scale_max']),
            'single_option_percentage'  => $this->calculateSingleOption($valid, $scoring['target_value']),
            'frequency_distribution'    => $this->calculateFrequencyDist($valid),
            'multi_average'             => $this->calculateMultiAverage($valid, $scoring),
            default                     => ['score' => null, 'summary' => 'Unknown scoring method'],
        };
    }

    private function calculateNps(array $scores): array
    {
        $promoters = $passives = $detractors = 0;
        foreach ($scores as $s) {
            $v = (int) $s;
            if ($v >= 9) {
                $promoters++;
            } elseif ($v >= 7) {
                $passives++;
            } else {
                $detractors++;
            }
        }
        $total    = count($scores);
        $npsScore = (int) round((($promoters - $detractors) / $total) * 100);

        return [
            'score'      => $npsScore,
            'summary'    => "NPS: {$npsScore} ({$total} responses)",
            'breakdown'  => [
                'promoters'    => $promoters,
                'promoter_pct' => round($promoters / $total * 100, 1),
                'passives'     => $passives,
                'passive_pct'  => round($passives / $total * 100, 1),
                'detractors'    => $detractors,
                'detractor_pct' => round($detractors / $total * 100, 1),
            ],
            'benchmarks' => ['excellent' => '>70', 'good' => '50-70', 'ok' => '0-50', 'poor' => '<0'],
        ];
    }

    private function calculateTopBox(array $scores, array $topBox): array
    {
        $topCount = 0;
        foreach ($scores as $s) {
            if (in_array((int) $s, $topBox, true)) {
                $topCount++;
            }
        }
        $total = count($scores);
        $pct   = round(($topCount / $total) * 100, 1);

        return [
            'score'      => $pct,
            'summary'    => "CSAT: {$pct}% ({$topCount}/{$total} gave 4 or 5)",
            'breakdown'  => $this->buildDistribution($scores, 1, 5),
            'benchmarks' => ['excellent' => '>90%', 'good' => '75-90%', 'average' => '50-75%', 'poor' => '<50%'],
        ];
    }

    private function calculateAverage(array $scores, int $max): array
    {
        $sum = array_sum(array_map('intval', $scores));
        $avg = round($sum / count($scores), 2);

        return [
            'score'     => $avg,
            'summary'   => "Average: {$avg}/{$max} (" . count($scores) . ' responses)',
            'breakdown' => $this->buildDistribution($scores, 1, $max),
        ];
    }

    private function calculateSingleOption(array $scores, string $targetValue): array
    {
        $targetCount = 0;
        $dist        = [];
        foreach ($scores as $s) {
            $v        = (string) $s;
            $dist[$v] = ($dist[$v] ?? 0) + 1;
            if ($v === $targetValue) {
                $targetCount++;
            }
        }
        $total = count($scores);
        $pct   = round(($targetCount / $total) * 100, 1);

        return [
            'score'      => $pct,
            'summary'    => "PMF: {$pct}% \"Very Disappointed\" ({$total} responses)",
            'breakdown'  => $dist,
            'benchmarks' => ['strong_pmf' => '>40%', 'promising' => '25-40%', 'weak' => '<25%'],
        ];
    }

    private function calculateFrequencyDist(array $scores): array
    {
        $dist = [];
        foreach ($scores as $s) {
            // Checkboxgrp values may be comma- or pipe-separated
            $options = is_string($s) ? preg_split('/[,|]/', $s) : [$s];
            foreach ($options as $opt) {
                $opt = trim((string) $opt);
                if ($opt !== '') {
                    $dist[$opt] = ($dist[$opt] ?? 0) + 1;
                }
            }
        }
        arsort($dist);

        $top = !empty($dist) ? array_key_first($dist) : 'N/A';

        return [
            'score'     => null,
            'summary'   => "Top reason: \"{$top}\" (" . ($dist[$top] ?? 0) . ' mentions, ' . count($scores) . ' responses)',
            'breakdown' => $dist,
        ];
    }

    private function calculateMultiAverage(array $scores, array $scoring): array
    {
        $fieldAverages = [];
        foreach ($scoring['score_fields'] as $sf) {
            $vals = array_filter(
                array_column($scores, $sf),
                fn($v) => $v !== null
            );
            $fieldAverages[$sf] = count($vals) > 0
                ? round(array_sum(array_map('intval', $vals)) / count($vals), 2)
                : null;
        }

        $nonNull    = array_filter($fieldAverages, fn($v) => $v !== null);
        $overallAvg = count($nonNull) > 0
            ? round(array_sum($nonNull) / count($nonNull), 2)
            : null;

        $scaleMax = $scoring['scale_max'] ?? 5;

        return [
            'score'     => $overallAvg,
            'summary'   => "Overall: {$overallAvg}/{$scaleMax} (" . count($scores) . ' responses)',
            'breakdown' => $fieldAverages,
        ];
    }

    /**
     * Build a value distribution histogram.
     */
    private function buildDistribution(array $scores, int $min, int $max): array
    {
        $dist = array_fill($min, $max - $min + 1, 0);
        foreach ($scores as $s) {
            $v = (int) $s;
            if (isset($dist[$v])) {
                $dist[$v]++;
            }
        }
        return $dist;
    }

    /**
     * AI-powered interpretation of survey results.
     */
    private function interpretSurveyResults(string $type, array $metric, array $verbatims): array
    {
        $sampleVerbatims = array_slice($verbatims, 0, 20);

        $prompt = "You are a VoC analytics expert. Interpret these survey results concisely.\n\n"
            . "Survey type: {$type}\n"
            . "Score/metric: " . json_encode($metric, JSON_UNESCAPED_UNICODE) . "\n"
            . (!empty($sampleVerbatims)
                ? "Sample verbatims:\n" . json_encode($sampleVerbatims, JSON_UNESCAPED_UNICODE) . "\n\n"
                : "\n")
            . "Respond ONLY with valid JSON:\n"
            . '{"interpretation":"2-3 sentence plain-English summary of what this score means",'
            . '"key_insight":"the single most important takeaway",'
            . '"recommended_action":"what to do next based on these results",'
            . '"urgency":"low|medium|high"}';

        try {
            $response = $this->mistralClient->complete([
                ['role' => 'user', 'content' => $prompt],
            ]);

            $raw = $response['content'] ?? '{}';
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $raw = $m[0];
            }
            return json_decode($raw, true) ?? ['interpretation' => 'Could not generate interpretation.'];
        } catch (\Throwable) {
            return ['interpretation' => 'AI interpretation unavailable.'];
        }
    }
}

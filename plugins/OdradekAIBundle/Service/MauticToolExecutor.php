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
                'list_reports'   => $this->listReports(),
                'get_report_data' => $this->getReportData($args),
                default          => ['success' => false, 'error' => "Unknown tool: {$tool}"],
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
}

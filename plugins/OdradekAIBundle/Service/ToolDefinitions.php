<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

class ToolDefinitions
{
    public static function getTools(): array
    {
        return [
            // ── Contacts ──────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_contacts',
                    'description' => 'List contacts in Mautic with optional search filter.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string', 'description' => 'Search term to filter contacts by name or email.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Maximum number of contacts to return. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_contact',
                    'description' => 'Retrieve full details of a single contact by ID.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The contact ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'create_contact',
                    'description' => 'Create a new contact in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'fields' => [
                                'type'        => 'object',
                                'description' => 'Contact field values. Common fields: firstname, lastname, email, phone, company.',
                                'properties'  => [
                                    'firstname' => ['type' => 'string'],
                                    'lastname'  => ['type' => 'string'],
                                    'email'     => ['type' => 'string'],
                                    'phone'     => ['type' => 'string'],
                                    'company'   => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'required' => ['fields'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_contact',
                    'description' => 'Update fields on an existing contact.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'     => ['type' => 'integer', 'description' => 'The contact ID to update.'],
                            'fields' => ['type' => 'object', 'description' => 'Field key/value pairs to update.'],
                        ],
                        'required' => ['id', 'fields'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'delete_contact',
                    'description' => 'DESTRUCTIVE: Permanently delete a contact. Only call this after the user has explicitly confirmed the deletion.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The contact ID to delete.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],

            // ── Emails ────────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_email_themes',
                    'description' => 'List available Mautic email themes. Call this before create_email to pick an appropriate visual theme.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => new \stdClass(),
                        'required'   => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_emails',
                    'description' => 'List email assets in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string', 'description' => 'Search term to filter emails by name or subject.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Maximum number to return. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_email',
                    'description' => 'Retrieve details of a single email asset by ID.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The email ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'create_email',
                    'description' => 'Create a new email asset in Mautic using a visual theme. '
                                   . 'Always call list_email_themes first, then pass the chosen theme as the template. '
                                   . 'Pass an empty string for body — do NOT write all the content upfront. '
                                   . 'After creation, call get_email_components to see what text slots the theme '
                                   . 'provides, then fill each one with update_email_component.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'      => ['type' => 'string', 'description' => 'Internal name for the email.'],
                            'subject'   => ['type' => 'string', 'description' => 'Email subject line.'],
                            'body'      => ['type' => 'string', 'description' => 'HTML body content. Pass empty string when using a theme — slots are filled via update_email_component.'],
                            'fromName'  => ['type' => 'string', 'description' => 'Sender display name.'],
                            'fromEmail' => ['type' => 'string', 'description' => 'Sender email address.'],
                            'template'  => ['type' => 'string', 'description' => 'Theme folder name (e.g. "aurora", "oxygen", "sparse"). Get valid names from list_email_themes.'],
                        ],
                        'required' => ['name', 'subject', 'body'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_email',
                    'description' => 'Update an existing email asset.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'     => ['type' => 'integer', 'description' => 'The email ID to update.'],
                            'params' => ['type' => 'object', 'description' => 'Fields to update (name, subject, body, fromName, fromEmail, etc.).'],
                        ],
                        'required' => ['id', 'params'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_email_components',
                    'description' => 'Read all editable text slots (mj-text blocks) from an email '
                                   . 'created with a GrapesJS theme. Returns the index and current '
                                   . 'placeholder text of each slot so you know what content to write '
                                   . 'for each one. Call this right after create_email.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The email ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_email_component',
                    'description' => 'Replace the content of a specific text slot (mj-text block) '
                                   . 'in a themed email by its index. Use the indexes returned by '
                                   . 'get_email_components. Provide HTML as inner content only — '
                                   . 'headings, paragraphs, links, lists; no full HTML wrapper.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'             => ['type' => 'integer',  'description' => 'The email ID.'],
                            'componentIndex' => ['type' => 'integer',  'description' => 'Zero-based index of the mj-text block (from get_email_components).'],
                            'html'           => ['type' => 'string',   'description' => 'New inner HTML for the slot.'],
                        ],
                        'required' => ['id', 'componentIndex', 'html'],
                    ],
                ],
            ],

            // ── Campaigns ─────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_campaigns',
                    'description' => 'List campaigns in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string', 'description' => 'Filter by campaign name.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Maximum number to return. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_campaign',
                    'description' => 'Get details of a campaign by ID.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The campaign ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],

            // ── Segments ─────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_segments',
                    'description' => 'List contact segments in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string', 'description' => 'Filter by segment name.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Maximum number to return. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'create_segment',
                    'description' => 'Create a new contact segment/list in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'  => ['type' => 'string', 'description' => 'Display name for the segment.'],
                            'alias' => ['type' => 'string', 'description' => 'URL-safe alias (optional, auto-generated if omitted).'],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],

            // ── Reports ───────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_reports',
                    'description' => 'List all reports available in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => new \stdClass(),
                        'required'   => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_report_data',
                    'description' => 'Retrieve the data rows from a specific report by ID.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The report ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],

            // ── Ethics & Intelligence ─────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'analyze_email_ethics',
                    'description' => 'Analyze an email for dark patterns, manipulative language, and EU AI Act compliance issues. '
                        . 'Call this proactively before creating or sending any email. '
                        . 'Returns an ethics score (0-100), list of issues with severity, and recommendations.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'email_id' => ['type' => 'integer', 'description' => 'Mautic email ID to fetch and analyze.'],
                            'content'  => ['type' => 'string', 'description' => 'Raw HTML email body to analyze (use before saving).'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'analyze_campaign_performance',
                    'description' => 'Get AI-powered insights on a campaign\'s performance. Fetches email metrics (sent, open rate) and returns analysis: what is working, what is not, and concrete improvement suggestions.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'campaign_id' => ['type' => 'integer', 'description' => 'The Mautic campaign ID to analyze.'],
                        ],
                        'required' => ['campaign_id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'suggest_campaign_journey',
                    'description' => 'Generate a structured email journey plan for a given marketing goal. Returns a sequence of emails with subjects, timing, purpose, and key messaging strategy.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'goal'       => ['type' => 'string', 'description' => 'The campaign goal, e.g. "welcome new subscribers", "re-engage cold leads", "upsell premium plan".'],
                            'audience'   => ['type' => 'string', 'description' => 'Target audience description (optional).'],
                            'num_emails' => ['type' => 'integer', 'description' => 'Number of emails in the sequence (default 3, max 6).'],
                        ],
                        'required' => ['goal'],
                    ],
                ],
            ],

            // ── Compliance, Sentiment & Health ────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'generate_compliance_report',
                    'description' => 'Audit all emails in a campaign against EU AI Act and GDPR articles. Returns a pass/warning/fail status per regulation article, a compliance rate (0-100), critical issues, and top recommendations.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'campaign_id' => ['type' => 'integer', 'description' => 'The Mautic campaign ID to audit.'],
                        ],
                        'required' => ['campaign_id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'analyze_contact_sentiment',
                    'description' => 'Analyze the sentiment and engagement signals of a contact based on their profile data and activity. Returns sentiment (positive/neutral/negative), a score (0-100), key signals, topics of interest, engagement level, and a recommended next action.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'contact_id' => ['type' => 'integer', 'description' => 'The Mautic contact ID to analyze.'],
                        ],
                        'required' => ['contact_id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'score_contact_health',
                    'description' => 'Score a contact\'s engagement health (0-100) based on activity recency, lead score, and segment membership. Returns risk_level (healthy/moderate/at_risk/churning), strengths, concerns, and a recommended action.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'contact_id' => ['type' => 'integer', 'description' => 'The Mautic contact ID to score.'],
                        ],
                        'required' => ['contact_id'],
                    ],
                ],
            ],

            // ── Navigation / Page Context ─────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'navigate_mautic',
                    'description' => 'Navigate the embedded Mautic iframe to a specific path, e.g. /s/contacts or /s/campaigns. Use this to show the user relevant pages.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'path' => ['type' => 'string', 'description' => 'The Mautic path to navigate to, e.g. /s/contacts/view/42'],
                        ],
                        'required' => ['path'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_page_info',
                    'description' => 'Get the current URL and page title visible in the Mautic iframe. Use this to understand what the user is currently looking at.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => new \stdClass(),
                        'required'   => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_grapesjs_component',
                    'description' => 'Update a selected component in the GrapesJS email builder with new HTML. '
                        . 'When multiple components are selected, use componentIndex to target the right one. '
                        . 'Use for in-place edits: translate, rewrite, replace copy.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'html' => [
                                'type'        => 'string',
                                'description' => 'New inner HTML for the component.',
                            ],
                            'componentIndex' => [
                                'type'        => 'integer',
                                'description' => 'Zero-based index of the component to update when multiple '
                                    . 'are selected (from context.selectedComponents). Defaults to 0.',
                            ],
                        ],
                        'required' => ['html'],
                    ],
                ],
            ],
        ];
    }
}

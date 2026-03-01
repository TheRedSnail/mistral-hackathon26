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

            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_email_image_components',
                    'description' => 'Return all <mj-image> slots in a theme email\'s MJML, '
                        . 'with their 0-based index and current src URL. '
                        . 'Use this to find image placeholders before generating AI images.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'Email ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_email_image_component',
                    'description' => 'Replace the src URL of a specific <mj-image> slot in a theme email. '
                        . 'Use the imageIndex from get_email_image_components and the URL returned by generate_image_asset.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'         => ['type' => 'integer', 'description' => 'Email ID.'],
                            'imageIndex' => ['type' => 'integer', 'description' => '0-based index from get_email_image_components.'],
                            'imageUrl'   => ['type' => 'string',  'description' => 'Public URL of the generated asset (from generate_image_asset result).'],
                        ],
                        'required' => ['id', 'imageIndex', 'imageUrl'],
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
                    'name'        => 'get_segment',
                    'description' => 'Fetch a contact segment by ID. Returns id, name, alias, publicName, description, filters, and member count.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The segment ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_segment_filter_fields',
                    'description' => 'Returns a curated list of filterable contact fields with their alias, label, type, and valid operators. Always call this before constructing any segment filter array.',
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
                    'name'        => 'create_segment',
                    'description' => 'Create a new contact segment/list in Mautic. Call get_segment_filter_fields first if you need to add filters.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'        => ['type' => 'string', 'description' => 'Display name for the segment.'],
                            'alias'       => ['type' => 'string', 'description' => 'URL-safe alias (optional, auto-generated if omitted).'],
                            'publicName'  => ['type' => 'string', 'description' => 'Public-facing name shown to contacts (optional).'],
                            'description' => ['type' => 'string', 'description' => 'Internal description (optional).'],
                            'filters'     => [
                                'type'        => 'array',
                                'description' => 'Filter criteria. Each item: {glue, field, object, type, filter, operator}. glue is "and"|"or". Use get_segment_filter_fields to obtain valid field aliases and operators.',
                                'items'       => ['type' => 'object'],
                            ],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_segment',
                    'description' => 'Update an existing contact segment by ID. Provide only the fields you want to change. Call get_segment_filter_fields first if modifying filters.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'          => ['type' => 'integer', 'description' => 'The segment ID to update.'],
                            'name'        => ['type' => 'string', 'description' => 'New display name.'],
                            'alias'       => ['type' => 'string', 'description' => 'New URL-safe alias.'],
                            'publicName'  => ['type' => 'string', 'description' => 'New public-facing name.'],
                            'description' => ['type' => 'string', 'description' => 'New internal description.'],
                            'filters'     => [
                                'type'        => 'array',
                                'description' => 'Replacement filter array. Replaces all existing filters. Use get_segment_filter_fields to obtain valid field aliases and operators.',
                                'items'       => ['type' => 'object'],
                            ],
                        ],
                        'required' => ['id'],
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

            // ── Assets ────────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_asset_categories',
                    'description' => 'List existing asset categories so you can pick one when creating an image asset. '
                                   . 'If no fitting category exists, call create_asset_category first.',
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
                    'name'        => 'create_asset_category',
                    'description' => 'Create a new asset category. Call list_asset_categories first to avoid duplicates.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'title'       => ['type' => 'string', 'description' => 'Category display name.'],
                            'description' => ['type' => 'string', 'description' => 'Optional description.'],
                        ],
                        'required' => ['title'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'generate_image_asset',
                    'description' => 'Generate an image using the Gemini API and save it as a Mautic asset. '
                                   . 'Always call list_asset_categories first to pick or create the right category. '
                                   . 'Use language code "en" if the image contains no text or mixed-world text.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'prompt'      => ['type' => 'string', 'description' => 'Detailed image generation prompt for Gemini.'],
                            'title'       => ['type' => 'string', 'description' => 'Asset title shown in Mautic.'],
                            'description' => ['type' => 'string', 'description' => 'Optional description of the image.'],
                            'category_id' => ['type' => 'integer', 'description' => 'Asset category ID (from list_asset_categories or create_asset_category).'],
                            'language'    => ['type' => 'string', 'description' => 'Locale code for the language used in the image, e.g. "en", "de", "fr". Use "en" if no text in image. Default: "en".'],
                        ],
                        'required' => ['prompt', 'title'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_assets',
                    'description' => 'List Mautic assets (files/images) with optional search filter.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string', 'description' => 'Filter by asset title.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Max number to return. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_asset',
                    'description' => 'Get full details of a Mautic asset by ID.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The asset ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_asset',
                    'description' => 'Update metadata (title, description, category, language) of an existing Mautic asset.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'          => ['type' => 'integer', 'description' => 'The asset ID to update.'],
                            'title'       => ['type' => 'string', 'description' => 'New title.'],
                            'description' => ['type' => 'string', 'description' => 'New description.'],
                            'category_id' => ['type' => 'integer', 'description' => 'New category ID.'],
                            'language'    => ['type' => 'string', 'description' => 'New language code.'],
                            'disallow'    => ['type' => 'boolean', 'description' => 'Block search engines (true = yes). Default remains unchanged.'],
                        ],
                        'required' => ['id'],
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

            // ── Landing Pages ──────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_page_themes',
                    'description' => 'List available Mautic landing-page themes. Call before create_page to pick a visual theme.',
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
                    'name'        => 'list_pages',
                    'description' => 'List landing pages in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string',  'description' => 'Filter pages by title.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Max results. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_page',
                    'description' => 'Retrieve details of a single landing page by ID.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The page ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'create_page',
                    'description' => 'Create a new Mautic landing page. '
                        . 'Generate well-structured HTML for the content field: semantic sections '
                        . '(hero headline + CTA button, feature/benefit blocks, closing CTA). '
                        . 'Use inline CSS for visual polish. Do NOT include <html><head><body> tags — '
                        . 'only the inner content sections. The theme wraps your content with visual chrome.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'title'           => ['type' => 'string',  'description' => 'Page title (heading + tab title).'],
                            'alias'           => ['type' => 'string',  'description' => 'URL slug for /p/{alias}. Auto-generated from title if omitted.'],
                            'template'        => ['type' => 'string',  'description' => 'Theme folder name from list_page_themes.'],
                            'content'         => ['type' => 'string',  'description' => 'HTML body content — no <html><head><body> wrapper tags.'],
                            'metaDescription' => ['type' => 'string',  'description' => 'SEO meta description (under 160 chars).'],
                            'isPublished'     => ['type' => 'boolean', 'description' => 'Publish immediately. Default true.'],
                        ],
                        'required' => ['title', 'content'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_page',
                    'description' => 'Update an existing landing page.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'     => ['type' => 'integer', 'description' => 'The page ID.'],
                            'params' => ['type' => 'object',  'description' => 'Fields to update: title, content, template, alias, metaDescription, isPublished.'],
                        ],
                        'required' => ['id', 'params'],
                    ],
                ],
            ],

            // ── Forms ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_forms',
                    'description' => 'List Mautic forms.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'search' => ['type' => 'string',  'description' => 'Filter by name.'],
                            'limit'  => ['type' => 'integer', 'description' => 'Max results. Default 20.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'get_form',
                    'description' => 'Get details of a Mautic form including its fields and actions.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'The form ID.'],
                        ],
                        'required' => ['id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'create_form',
                    'description' => 'Create a new Mautic form with fields and submit actions. '
                        . 'Always include an email field (mapped to contact email). '
                        . 'Always end with a submit button (type: button). '
                        . 'Map fields to contact properties via mappedObject/mappedField. '
                        . 'Add a lead.changelist action to enrol the contact in a relevant segment.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'               => ['type' => 'string',  'description' => 'Form name.'],
                            'description'        => ['type' => 'string',  'description' => 'Optional description.'],
                            'formType'           => ['type' => 'string',  'description' => '"standalone" (default) or "campaign".'],
                            'postAction'         => ['type' => 'string',  'description' => '"message" (default), "redirect", or "return".'],
                            'postActionProperty' => ['type' => 'string',  'description' => 'Thank-you message text (for "message") or redirect URL (for "redirect").'],
                            'isPublished'        => ['type' => 'boolean', 'description' => 'Publish immediately. Default true.'],
                            'fields'             => [
                                'type'        => 'array',
                                'description' => 'Ordered list of form fields. Each field: label (string), type (string), alias (string, snake_case), isRequired (bool), mappedObject ("contact"|"company"|null), mappedField (contact field alias|null), helpMessage (string), properties (object, for select/radio/checkbox: {optionlist:{list:[{label,value}]}}), order (int).',
                                'items'       => ['type' => 'object'],
                            ],
                            'actions'            => [
                                'type'        => 'array',
                                'description' => 'Submit actions. Each action: type (string e.g. "lead.changelist"), name (string), properties (object — e.g. {addToLists:[segmentId], removeFromLists:[]}).',
                                'items'       => ['type' => 'object'],
                            ],
                        ],
                        'required' => ['name', 'fields'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'update_form',
                    'description' => 'Update an existing Mautic form (name, description, postAction, postActionProperty, isPublished).',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'id'     => ['type' => 'integer', 'description' => 'Form ID.'],
                            'params' => ['type' => 'object',  'description' => 'Fields to update: name, description, postAction, postActionProperty, isPublished.'],
                        ],
                        'required' => ['id', 'params'],
                    ],
                ],
            ],

            // ── Voice of Customer (VoC) Analytics ────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'voc_collect_feedback',
                    'description' => 'Aggregate customer verbatims from forms, notes, DNC comments, and email engagement. '
                        . 'All text is PII-redacted before returning. Use this as the first step in any VoC analysis workflow.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'source'   => ['type' => 'string',  'description' => 'Data source filter: "all" (default), "forms", "notes", "dnc", "email_engagement".'],
                            'form_ids' => [
                                'type'        => 'array',
                                'items'       => ['type' => 'integer'],
                                'description' => 'Specific form IDs to collect from (optional, default = all forms).',
                            ],
                            'date_from' => ['type' => 'string', 'description' => 'Start date (Y-m-d). Default: 90 days ago.'],
                            'date_to'   => ['type' => 'string', 'description' => 'End date (Y-m-d). Default: today.'],
                            'limit'     => ['type' => 'integer', 'description' => 'Max verbatims to return. Default 200.'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'voc_analyze_themes',
                    'description' => 'AI-powered topic extraction with per-theme sentiment on collected verbatims. '
                        . 'Automatically calls voc_collect_feedback if no verbatims are provided. '
                        . 'Returns themes with name, sentiment, intensity, count, representative quotes, and trend.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'source'   => ['type' => 'string',  'description' => 'Data source filter passed to collection: "all", "forms", "notes", "dnc", "email_engagement".'],
                            'form_ids' => [
                                'type'  => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Specific form IDs (optional).',
                            ],
                            'date_from' => ['type' => 'string', 'description' => 'Start date (Y-m-d).'],
                            'date_to'   => ['type' => 'string', 'description' => 'End date (Y-m-d).'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'voc_contact_voice',
                    'description' => 'Deep VoC profile for a single contact — aggregates all sources (forms, notes, DNC, email engagement) '
                        . 'and provides sentiment, topics, churn signals, key quotes, recommended action, and urgency.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'contact_id' => ['type' => 'integer', 'description' => 'The Mautic contact ID.'],
                        ],
                        'required' => ['contact_id'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'voc_summarize_theme',
                    'description' => 'Detailed AI summary of a specific VoC theme — drill down into a topic discovered by voc_analyze_themes. '
                        . 'Returns summary, representative quotes, trend, severity, and recommended action.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'theme_name' => ['type' => 'string', 'description' => 'The theme name to drill into (from voc_analyze_themes results).'],
                            'source'     => ['type' => 'string', 'description' => 'Data source filter (optional, default "all").'],
                            'form_ids'   => [
                                'type'  => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Specific form IDs (optional).',
                            ],
                        ],
                        'required' => ['theme_name'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'voc_create_insight_segment',
                    'description' => 'Create a static Mautic segment from VoC insight — add specific contacts identified by VoC analysis '
                        . 'to a new segment for targeted follow-up campaigns.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'        => ['type' => 'string', 'description' => 'Segment name (e.g. "VoC: Pricing Concerns").'],
                            'description' => ['type' => 'string', 'description' => 'Description of the insight that created this segment.'],
                            'contact_ids' => [
                                'type'        => 'array',
                                'items'       => ['type' => 'integer'],
                                'description' => 'Contact IDs to add to the segment.',
                            ],
                        ],
                        'required' => ['name', 'contact_ids'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'voc_suggest_response_campaign',
                    'description' => 'Generate an AI-powered response campaign plan for a VoC theme. '
                        . 'Returns a multi-email journey (3-5 steps) tailored to address the theme\'s sentiment and urgency.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'theme'     => ['type' => 'string', 'description' => 'The theme to respond to (e.g. "Pricing Concerns").'],
                            'sentiment' => ['type' => 'string', 'description' => 'Theme sentiment: "positive", "negative", "neutral", or "mixed".'],
                            'audience'  => ['type' => 'string', 'description' => 'Target audience description (optional).'],
                            'context'   => ['type' => 'string', 'description' => 'Additional context from the VoC analysis (optional).'],
                        ],
                        'required' => ['theme', 'sentiment'],
                    ],
                ],
            ],

            // ── VoC Survey Templates ─────────────────────────────────────
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'list_survey_templates',
                    'description' => 'List available VoC survey templates with descriptions, question types, scoring methods, and benchmarks. '
                        . 'Use this when the user asks what surveys are available or wants to choose a template.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => (object) [],
                        'required'   => [],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'create_survey',
                    'description' => 'Create a VoC survey from a pre-built template. Builds a complete Mautic form with the correct '
                        . 'question types, scales, and field aliases for automated scoring. '
                        . 'Templates: nps (Net Promoter Score 0-10), csat (Customer Satisfaction 1-5), '
                        . 'ces (Customer Effort Score 1-7), pmf (Product-Market Fit), onboarding (Onboarding Feedback), '
                        . 'churn (Exit Survey), post_purchase (Post-Purchase). '
                        . 'After creating, use survey_analytics with the form_id to compute results.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'template'         => [
                                'type'        => 'string',
                                'enum'        => ['nps', 'csat', 'ces', 'pmf', 'onboarding', 'churn', 'post_purchase'],
                                'description' => 'Survey template to use.',
                            ],
                            'company_name'     => [
                                'type'        => 'string',
                                'description' => 'Company name to personalize the survey questions (optional).',
                            ],
                            'product_name'     => [
                                'type'        => 'string',
                                'description' => 'Product or service name to reference in questions (optional).',
                            ],
                            'custom_follow_up' => [
                                'type'        => 'string',
                                'description' => 'Custom follow-up question to replace the default open-ended question (optional).',
                            ],
                        ],
                        'required' => ['template'],
                    ],
                ],
            ],
            [
                'type'     => 'function',
                'function' => [
                    'name'        => 'survey_analytics',
                    'description' => 'Calculate survey metrics (NPS score, CSAT %, CES average, PMF %) from form responses. '
                        . 'Automatically detects the survey type from field aliases set by create_survey. '
                        . 'Returns the computed score, response breakdown, response count, benchmarks, and AI-powered interpretation.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'form_id'   => [
                                'type'        => 'integer',
                                'description' => 'The Mautic form ID of the survey to analyze.',
                            ],
                            'date_from' => [
                                'type'        => 'string',
                                'description' => 'Start date filter in Y-m-d format (optional).',
                            ],
                            'date_to'   => [
                                'type'        => 'string',
                                'description' => 'End date filter in Y-m-d format (optional).',
                            ],
                        ],
                        'required' => ['form_id'],
                    ],
                ],
            ],
        ];
    }
}

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
                    'description' => 'Create a new email asset in Mautic.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'name'      => ['type' => 'string', 'description' => 'Internal name for the email.'],
                            'subject'   => ['type' => 'string', 'description' => 'Email subject line.'],
                            'body'      => ['type' => 'string', 'description' => 'HTML body content.'],
                            'fromName'  => ['type' => 'string', 'description' => 'Sender display name.'],
                            'fromEmail' => ['type' => 'string', 'description' => 'Sender email address.'],
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
        ];
    }
}

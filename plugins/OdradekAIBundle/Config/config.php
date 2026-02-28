<?php

declare(strict_types=1);

return [
    'name'        => 'Odradek AI',
    'description' => 'AI assistant powered by Mistral that can autonomously manage Mautic entities via natural language.',
    'version'     => '1.0.0',
    'author'      => 'Odradek',

    'routes' => [
        'main' => [
            'odradek_ai_index' => [
                'path'       => '/odradek/ai',
                'controller' => 'MauticPlugin\OdradekAIBundle\Controller\AiController::indexAction',
                'method'     => 'GET',
            ],
            'odradek_ai_chat' => [
                'path'       => '/odradek/ai/chat',
                'controller' => 'MauticPlugin\OdradekAIBundle\Controller\ChatController::chatAction',
                'method'     => 'POST',
            ],
        ],
    ],

    'parameters' => [
        'odradek_ai_api_key'    => '',
        'odradek_ai_model'      => 'mistral-large-latest',
        'odradek_ai_enabled'    => false,
        'odradek_ai_max_tokens' => 8000,
    ],
];

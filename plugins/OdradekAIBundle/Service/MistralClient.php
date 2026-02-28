<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MistralClient
{
    private const API_URL = 'https://api.mistral.ai/v1/chat/completions';

    public function __construct(
        private readonly HttpClientInterface   $http,
        private readonly CoreParametersHelper  $parametersHelper,
    ) {}

    /**
     * Send a chat completion request to Mistral.
     *
     * Returns a normalized array:
     *   [
     *     'role'       => 'assistant',
     *     'content'    => '...',   // may be null if only tool calls
     *     'tool_calls' => [        // may be empty
     *       ['id' => '...', 'function' => ['name' => '...', 'arguments' => '...']]
     *     ]
     *   ]
     *
     * @throws \RuntimeException on HTTP or API error
     */
    public function complete(array $messages, array $tools = [], string $toolChoice = 'auto'): array
    {
        $apiKey = $this->parametersHelper->get('odradek_ai_api_key');
        $model  = $this->parametersHelper->get('odradek_ai_model') ?: 'mistral-large-latest';

        if (empty($apiKey)) {
            throw new \RuntimeException('Mistral API key is not configured. Please go to Settings → Configuration → AI Settings.');
        }

        $payload = [
            'model'               => $model,
            'messages'            => $messages,
            'stream'              => false,
            'parallel_tool_calls' => true,
            'max_tokens'          => (int) ($this->parametersHelper->get('odradek_ai_max_tokens') ?: 8000),
        ];

        if (!empty($tools)) {
            $payload['tools']       = $tools;
            $payload['tool_choice'] = $toolChoice;
        }

        $response = $this->http->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json'         => $payload,
            'timeout'      => 120,   // inactivity timeout (Symfony maps this to curl's idle tracking; default is PHP's default_socket_timeout ~60s)
            'max_duration' => 300,   // hard cap of 5 minutes total
        ]);

        $statusCode = $response->getStatusCode();
        $body       = $response->toArray(false);

        if ($statusCode !== 200) {
            $errorMsg = $body['message'] ?? $body['error']['message'] ?? 'Unknown Mistral API error';
            throw new \RuntimeException("Mistral API error ({$statusCode}): {$errorMsg}");
        }

        $choice  = $body['choices'][0] ?? null;
        if (!$choice) {
            throw new \RuntimeException('Empty response from Mistral API.');
        }
        $message    = $choice['message'];
        $toolCalls  = [];

        if (!empty($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $tc) {
                $toolCalls[] = [
                    'id'       => $tc['id'],
                    'function' => [
                        'name'      => $tc['function']['name'],
                        'arguments' => is_array($tc['function']['arguments'])
                            ? json_encode($tc['function']['arguments'])
                            : $tc['function']['arguments'],
                    ],
                ];
            }
        }

        return [
            'role'       => 'assistant',
            'content'    => $message['content'] ?? null,
            'tool_calls' => $toolCalls,
        ];
    }
}

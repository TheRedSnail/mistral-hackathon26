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
            'stream'              => true,   // streaming: Mistral sends chunks as generated, preventing idle timeout
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
                'Accept'        => 'text/event-stream',
            ],
            'json'         => $payload,
            'timeout'      => 120,   // max seconds to wait between chunks — Mistral can take time to start streaming large batches
            'max_duration' => 480,   // hard cap of 8 minutes total
        ]);

        // Wait for headers; non-200 responses are small so toArray() is safe here
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $body     = $response->toArray(false);
            $errorMsg = $body['message'] ?? $body['error']['message'] ?? 'Unknown Mistral API error';
            throw new \RuntimeException("Mistral API error ({$statusCode}): {$errorMsg}");
        }

        // Accumulate SSE stream
        $content   = '';
        $toolCalls = [];   // keyed by tool-call index
        $buffer    = '';

        foreach ($this->http->stream($response) as $chunk) {
            $buffer .= $chunk->getContent();

            // Process every complete line in the buffer
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line   = trim($line);

                if ($line === '' || !str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6);
                if ($data === '[DONE]') {
                    break 2;
                }

                $event = json_decode($data, true);
                if (!$event) {
                    continue;
                }

                $delta = $event['choices'][0]['delta'] ?? [];

                if (isset($delta['content'])) {
                    $content .= $delta['content'];
                }

                foreach ($delta['tool_calls'] ?? [] as $tc) {
                    $idx = $tc['index'] ?? 0;
                    if (!isset($toolCalls[$idx])) {
                        $toolCalls[$idx] = ['id' => '', 'name' => '', 'arguments' => ''];
                    }
                    if (!empty($tc['id']))                    { $toolCalls[$idx]['id']         = $tc['id']; }
                    if (!empty($tc['function']['name']))      { $toolCalls[$idx]['name']        = $tc['function']['name']; }
                    if (isset($tc['function']['arguments']))  { $toolCalls[$idx]['arguments']  .= $tc['function']['arguments']; }
                }
            }
        }

        if ($content === '' && empty($toolCalls)) {
            throw new \RuntimeException('Empty response from Mistral API.');
        }

        // Normalise into the same shape the rest of the code expects
        ksort($toolCalls);
        $normalized = [];
        foreach ($toolCalls as $tc) {
            $normalized[] = [
                'id'       => $tc['id'],
                'function' => [
                    'name'      => $tc['name'],
                    'arguments' => $tc['arguments'],
                ],
            ];
        }

        return [
            'role'       => 'assistant',
            'content'    => $content ?: null,
            'tool_calls' => $normalized,
        ];
    }
}

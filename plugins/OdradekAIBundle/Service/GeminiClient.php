<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class GeminiClient
{
    private const MODEL    = 'gemini-3.1-flash-image-preview';
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(
        private readonly CoreParametersHelper $parametersHelper,
    ) {}

    /** Returns ['mimeType' => 'image/png', 'data' => '<base64>'] or throws \RuntimeException. */
    public function generateImage(string $prompt): array
    {
        $apiKey = (string) $this->parametersHelper->get('odradek_ai_gemini_api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured. Add it under Settings → Configuration → AI Settings.');
        }

        $url  = self::BASE_URL . self::MODEL . ':generateContent?key=' . urlencode($apiKey);
        $body = json_encode([
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['responseModalities' => ['image', 'text']],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $decoded = $response ? (json_decode($response, true) ?? []) : [];
            $msg = $decoded['error']['message'] ?? "Gemini API error (HTTP $httpCode)";
            throw new \RuntimeException($msg);
        }

        $data  = json_decode($response, true);
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $part) {
            if (isset($part['inlineData']['mimeType']) && str_starts_with($part['inlineData']['mimeType'], 'image/')) {
                return [
                    'mimeType' => $part['inlineData']['mimeType'],
                    'data'     => $part['inlineData']['data'],
                ];
            }
        }

        throw new \RuntimeException('Gemini returned no image data. Check the prompt and model availability.');
    }
}

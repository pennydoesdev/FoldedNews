<?php

namespace FoldedNews\Newsroom\Ai\Provider;

use FoldedNews\Newsroom\Ai\AiProviderInterface;
use FoldedNews\Newsroom\Ai\AiResult;

/**
 * Google Gemini generateContent. @link https://ai.google.dev/gemini-api/docs
 */
final class GeminiProvider implements AiProviderInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) getenv('GEMINI_API_KEY');
    }

    public function id(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Google Gemini';
    }

    public function available(): bool
    {
        return $this->apiKey !== '';
    }

    public function defaultModel(): string
    {
        return (string) (getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash');
    }

    public function complete(string $system, string $prompt, string $model, array $options = []): AiResult
    {
        if (! $this->available()) {
            return AiResult::fail('gemini', $model, 'unavailable');
        }

        $model = $model !== '' ? $model : $this->defaultModel();
        $url = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', rawurlencode($model), rawurlencode($this->apiKey));

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 60,
            'body' => (string) wp_json_encode([
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            ]),
        ]);

        if (is_wp_error($response)) {
            return AiResult::fail('gemini', $model, $response->get_error_message());
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (! is_array($data) || ! is_string($text) || $text === '') {
            $error = is_array($data) ? ($data['error']['message'] ?? 'empty_response') : 'bad_response';

            return AiResult::fail('gemini', $model, is_string($error) ? $error : 'empty_response');
        }

        return new AiResult(
            true,
            $text,
            'gemini',
            $model,
            (int) ($data['usageMetadata']['promptTokenCount'] ?? 0),
            (int) ($data['usageMetadata']['candidatesTokenCount'] ?? 0),
        );
    }
}

<?php

namespace FoldedNews\Newsroom\Ai\Provider;

use FoldedNews\Newsroom\Ai\AiProviderInterface;
use FoldedNews\Newsroom\Ai\AiResult;

/**
 * Anthropic Messages API. @link https://docs.claude.com/
 */
final class AnthropicProvider implements AiProviderInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) getenv('ANTHROPIC_API_KEY');
    }

    public function id(): string
    {
        return 'anthropic';
    }

    public function label(): string
    {
        return 'Anthropic';
    }

    public function available(): bool
    {
        return $this->apiKey !== '';
    }

    public function defaultModel(): string
    {
        return (string) (getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-haiku-latest');
    }

    public function complete(string $system, string $prompt, string $model, array $options = []): AiResult
    {
        if (! $this->available()) {
            return AiResult::fail('anthropic', $model, 'unavailable');
        }

        $model = $model !== '' ? $model : $this->defaultModel();

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 60,
            'body' => (string) wp_json_encode([
                'model' => $model,
                'max_tokens' => (int) ($options['max_tokens'] ?? 1024),
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]),
        ]);

        if (is_wp_error($response)) {
            return AiResult::fail('anthropic', $model, $response->get_error_message());
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        $text = $data['content'][0]['text'] ?? null;
        if (! is_array($data) || ! is_string($text) || $text === '') {
            $error = is_array($data) ? ($data['error']['message'] ?? 'empty_response') : 'bad_response';

            return AiResult::fail('anthropic', $model, is_string($error) ? $error : 'empty_response');
        }

        return new AiResult(
            true,
            $text,
            'anthropic',
            $model,
            (int) ($data['usage']['input_tokens'] ?? 0),
            (int) ($data['usage']['output_tokens'] ?? 0),
        );
    }
}

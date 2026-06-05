<?php

namespace FoldedNews\Newsroom\Ai\Provider;

use FoldedNews\Newsroom\Ai\AiProviderInterface;
use FoldedNews\Newsroom\Ai\AiResult;

/**
 * OpenAI-compatible Chat Completions provider. Base for OpenAI, MiniMax,
 * Featherless, self-hosted Llama/open-weight, and any custom compatible endpoint.
 */
class OpenCompatibleProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $label,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $defaultModel,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function available(): bool
    {
        return $this->apiKey !== '' && $this->baseUrl !== '';
    }

    public function defaultModel(): string
    {
        return $this->defaultModel;
    }

    public function complete(string $system, string $prompt, string $model, array $options = []): AiResult
    {
        if (! $this->available()) {
            return AiResult::fail($this->id, $model, 'unavailable');
        }

        $model = $model !== '' ? $model : $this->defaultModel;

        $response = wp_remote_post(rtrim($this->baseUrl, '/').'/chat/completions', [
            'headers' => ['Authorization' => 'Bearer '.$this->apiKey, 'Content-Type' => 'application/json'],
            'timeout' => 60,
            'body' => (string) wp_json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.4,
            ]),
        ]);

        if (is_wp_error($response)) {
            return AiResult::fail($this->id, $model, $response->get_error_message());
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($data)) {
            return AiResult::fail($this->id, $model, 'bad_response');
        }

        $text = $data['choices'][0]['message']['content'] ?? null;
        if (! is_string($text) || $text === '') {
            $error = $data['error']['message'] ?? 'empty_response';

            return AiResult::fail($this->id, $model, is_string($error) ? $error : 'empty_response');
        }

        return new AiResult(
            true,
            $text,
            $this->id,
            $model,
            (int) ($data['usage']['prompt_tokens'] ?? 0),
            (int) ($data['usage']['completion_tokens'] ?? 0),
        );
    }
}

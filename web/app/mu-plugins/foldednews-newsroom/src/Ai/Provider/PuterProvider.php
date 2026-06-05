<?php

namespace FoldedNews\Newsroom\Ai\Provider;

use FoldedNews\Newsroom\Ai\AiProviderInterface;
use FoldedNews\Newsroom\Ai\AiResult;

/**
 * Puter (https://docs.puter.com/). Puter's AI is primarily a client-side
 * ("user pays") SDK; server-side use requires a token. Disabled until
 * PUTER_API_KEY is set; see docs/ai-providers.md.
 */
final class PuterProvider implements AiProviderInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) getenv('PUTER_API_KEY');
    }

    public function id(): string
    {
        return 'puter';
    }

    public function label(): string
    {
        return 'Puter';
    }

    public function available(): bool
    {
        return $this->apiKey !== '';
    }

    public function defaultModel(): string
    {
        return (string) (getenv('PUTER_MODEL') ?: 'gpt-4o-mini');
    }

    public function complete(string $system, string $prompt, string $model, array $options = []): AiResult
    {
        return AiResult::fail('puter', $model, 'Puter is configured client-side; server-side use requires a token (see docs/ai-providers.md).');
    }
}

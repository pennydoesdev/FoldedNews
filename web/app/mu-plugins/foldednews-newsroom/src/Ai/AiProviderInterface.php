<?php

namespace FoldedNews\Newsroom\Ai;

/**
 * A replaceable AI provider. Every provider implements this; the router treats
 * them interchangeably.
 */
interface AiProviderInterface
{
    public function id(): string;

    public function label(): string;

    /** True when credentials are configured (keys live in env, never in options). */
    public function available(): bool;

    public function defaultModel(): string;

    /**
     * @param  array<string, mixed>  $options
     */
    public function complete(string $system, string $prompt, string $model, array $options = []): AiResult;
}

<?php

namespace FoldedNews\Newsroom\Ai;

/**
 * Immutable result of an AI completion.
 */
final class AiResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $text,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly string $error = '',
    ) {}

    public static function fail(string $provider, string $model, string $error): self
    {
        return new self(false, '', $provider, $model, 0, 0, $error);
    }
}

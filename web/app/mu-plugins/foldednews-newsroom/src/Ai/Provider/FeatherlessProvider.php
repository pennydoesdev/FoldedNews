<?php

namespace FoldedNews\Newsroom\Ai\Provider;

/**
 * Featherless AI (OpenAI-compatible) — serves open-weight models incl. Llama.
 * Confirm base URL + model from https://featherless.ai/; override via env.
 */
final class FeatherlessProvider extends OpenCompatibleProvider
{
    public function __construct()
    {
        parent::__construct(
            'featherless',
            'Featherless AI',
            (string) (getenv('FEATHERLESS_BASE_URL') ?: 'https://api.featherless.ai/v1'),
            (string) getenv('FEATHERLESS_API_KEY'),
            (string) (getenv('FEATHERLESS_MODEL') ?: 'meta-llama/Meta-Llama-3.1-8B-Instruct'),
        );
    }
}

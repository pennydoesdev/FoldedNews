<?php

namespace FoldedNews\Newsroom\Ai\Provider;

final class OpenAiProvider extends OpenCompatibleProvider
{
    public function __construct()
    {
        parent::__construct('openai', 'OpenAI', 'https://api.openai.com/v1', (string) getenv('OPENAI_API_KEY'), 'gpt-4o-mini');
    }
}

<?php

namespace FoldedNews\Newsroom\Ai\Provider;

/**
 * MiniMax via its OpenAI-compatible endpoint. Confirm the base URL + model from
 * the official docs (https://www.minimax.io/platform); override via env.
 */
final class MiniMaxProvider extends OpenCompatibleProvider
{
    public function __construct()
    {
        parent::__construct(
            'minimax',
            'MiniMax',
            (string) (getenv('MINIMAX_BASE_URL') ?: 'https://api.minimax.io/v1'),
            (string) getenv('MINIMAX_API_KEY'),
            (string) (getenv('MINIMAX_MODEL') ?: 'MiniMax-Text-01'),
        );
    }
}

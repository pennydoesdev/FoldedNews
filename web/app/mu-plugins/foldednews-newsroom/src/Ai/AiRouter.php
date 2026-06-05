<?php

namespace FoldedNews\Newsroom\Ai;

use FoldedNews\Newsroom\Ai\Provider\AnthropicProvider;
use FoldedNews\Newsroom\Ai\Provider\FeatherlessProvider;
use FoldedNews\Newsroom\Ai\Provider\GeminiProvider;
use FoldedNews\Newsroom\Ai\Provider\MiniMaxProvider;
use FoldedNews\Newsroom\Ai\Provider\OpenAiProvider;
use FoldedNews\Newsroom\Ai\Provider\OpenCompatibleProvider;
use FoldedNews\Newsroom\Ai\Provider\PuterProvider;

/**
 * Routes a feature to its configured provider/model, falls back to a secondary
 * provider, logs the call, and tags whether the output needs human approval.
 * Output is returned as a draft — never published automatically.
 */
final class AiRouter
{
    /**
     * @return array<string, AiProviderInterface>
     */
    public static function providers(): array
    {
        $providers = [
            new OpenAiProvider(),
            new AnthropicProvider(),
            new GeminiProvider(),
            new MiniMaxProvider(),
            new FeatherlessProvider(),
            new PuterProvider(),
            new OpenCompatibleProvider(
                'custom',
                'Custom (OpenAI-compatible)',
                (string) getenv('AI_OPENAI_COMPAT_BASE_URL'),
                (string) getenv('AI_OPENAI_COMPAT_KEY'),
                (string) (getenv('AI_OPENAI_COMPAT_MODEL') ?: 'gpt-4o-mini'),
            ),
        ];

        $map = [];
        foreach ($providers as $provider) {
            $map[$provider->id()] = $provider;
        }

        /** @var array<string, AiProviderInterface> $map */
        $map = apply_filters('foldednews/ai/providers', $map);

        return $map;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, text: string, provider: string, model: string, cost: float, requires_approval: bool, error: string}
     */
    public static function run(string $feature, string $input, array $options = []): array
    {
        $providers = self::providers();
        $config = get_option('fn_ai_features', []);
        $config = is_array($config) ? $config : [];

        $primary = is_array($config[$feature] ?? null) ? $config[$feature] : [];
        $primaryId = (string) ($primary['provider'] ?? get_option('fn_ai_default_provider', 'openai'));
        $model = (string) ($primary['model'] ?? '');
        $fallbackId = (string) get_option('fn_ai_fallback_provider', '');

        $system = AiPromptRegistry::system($feature);
        $result = null;

        foreach (array_filter([$primaryId, $fallbackId]) as $providerId) {
            $provider = $providers[$providerId] ?? null;
            if (! $provider instanceof AiProviderInterface || ! $provider->available()) {
                continue;
            }

            $result = $provider->complete($system, $input, $model !== '' ? $model : $provider->defaultModel(), $options);
            if ($result->ok) {
                break;
            }
        }

        $result ??= AiResult::fail($primaryId, $model, 'no_available_provider');
        $cost = AiCostEstimator::estimate($result->model, $result->promptTokens, $result->completionTokens);
        AiUsageLogger::log($feature, $result, $cost);

        return [
            'ok' => $result->ok,
            'text' => $result->text,
            'provider' => $result->provider,
            'model' => $result->model,
            'cost' => $cost,
            'requires_approval' => AiSafetyService::requiresApproval($feature),
            'error' => $result->error,
        ];
    }
}

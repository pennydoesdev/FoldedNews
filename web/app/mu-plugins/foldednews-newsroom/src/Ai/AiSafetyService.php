<?php

namespace FoldedNews\Newsroom\Ai;

/**
 * Editorial safety policy. AI output is *never* auto-published — the router only
 * returns drafts. Sensitive features additionally require explicit human
 * approval before an editor applies them (editorial / legal / breaking claims).
 */
final class AiSafetyService
{
    /**
     * Features whose output must be human-reviewed before use.
     *
     * @return list<string>
     */
    public static function sensitive(): array
    {
        return apply_filters('foldednews/ai/sensitive', [
            'editorial_notes', 'moderation', 'liveblog_cleanup', 'newsletter', 'chatbot', 'headline',
        ]);
    }

    public static function requiresApproval(string $feature): bool
    {
        return in_array($feature, self::sensitive(), true);
    }

    /**
     * AI never publishes automatically anywhere in the platform.
     */
    public static function autoPublishAllowed(): bool
    {
        return false;
    }
}

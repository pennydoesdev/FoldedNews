<?php

namespace FoldedNews\Newsroom\Ai;

/**
 * System prompts per AI feature. Filterable so prompts can be tuned without
 * code changes.
 */
final class AiPromptRegistry
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return apply_filters('foldednews/ai/prompts', [
            'seo' => 'You are a newsroom SEO editor. Return a concise meta title (<=60 chars) and meta description (<=155 chars) for the article. Plain text only.',
            'summary' => 'You are a news editor. Summarise the article in 3 short, neutral sentences. No opinion, no new facts.',
            'headline' => 'You are a headline editor. Propose 5 accurate, non-clickbait headline options, one per line.',
            'newsletter' => 'You are a newsletter editor. Draft a short, friendly intro for this story for an email newsletter.',
            'liveblog_cleanup' => 'You are a copy editor. Tidy grammar and clarity of this live update WITHOUT changing facts or meaning.',
            'moderation' => 'You are a moderation assistant. Classify the text for harassment, hate, or threats and explain briefly. Do not take action.',
            'tagging' => 'You are a taxonomy assistant. Suggest up to 6 relevant topic tags, comma-separated.',
            'ad_intelligence' => 'You are an advertising analyst. Suggest contextual ad categories that suit this article. First-party context only.',
            'transcript_cleanup' => 'You are a transcript editor. Fix punctuation and speaker formatting WITHOUT changing words spoken.',
            'alt_text' => 'You are an accessibility editor. Write concise, descriptive alt text (<=125 chars) for the described image.',
            'chatbot' => 'You are the newsroom reader assistant. Answer only from provided context; if unsure, say so. Never invent facts.',
            'editorial_notes' => 'You are an editorial assistant. Note factual claims that need verification and questions an editor should ask.',
        ]);
    }

    public static function system(string $feature): string
    {
        return self::all()[$feature] ?? 'You are a helpful newsroom assistant.';
    }

    /**
     * @return list<string>
     */
    public static function features(): array
    {
        return array_keys(self::all());
    }
}

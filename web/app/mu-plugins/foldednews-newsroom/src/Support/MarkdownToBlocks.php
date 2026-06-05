<?php

namespace FoldedNews\Newsroom\Support;

/**
 * Converts newsroom Markdown to Gutenberg-compatible block markup.
 *
 * Pure PHP (no WordPress) so it is unit-testable and side-effect free. Supports
 * headings, paragraphs, lists, blockquotes, fenced code, rules, inline
 * formatting, and the newsroom ::: directives (mapped to group blocks for now;
 * Stage 7 replaces map/chart/diagram with interactive blocks).
 */
final class MarkdownToBlocks
{
    /** Known ::: directives → callout label ('' = no label). */
    private const DIRECTIVES = [
        'note' => 'Note',
        'context' => 'Context',
        'source' => 'Source',
        'correction' => 'Correction',
        'methodology' => 'Methodology',
        'timeline' => 'Timeline',
        'live-update' => 'Live update',
        'map' => 'Map',
        'chart' => 'Chart',
        'diagram' => 'Diagram',
        'quote' => '',
    ];

    public static function convert(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];

        return trim(self::parse($lines));
    }

    /**
     * @param  list<string>  $lines
     */
    private static function parse(array $lines): string
    {
        $blocks = [];
        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '') {
                $i++;
                continue;
            }

            // ::: directive container
            if (preg_match('/^:::\s*([a-z-]+)\s*$/i', $trimmed, $m)) {
                $inner = [];
                $i++;
                while ($i < $n && trim($lines[$i]) !== ':::') {
                    $inner[] = $lines[$i];
                    $i++;
                }
                $i++; // closing :::
                $blocks[] = self::directive(strtolower($m[1]), self::parse($inner));
                continue;
            }

            // Fenced code
            if (str_starts_with($trimmed, '```')) {
                $code = [];
                $i++;
                while ($i < $n && ! str_starts_with(trim($lines[$i]), '```')) {
                    $code[] = $lines[$i];
                    $i++;
                }
                $i++; // closing fence
                $blocks[] = self::code(implode("\n", $code));
                continue;
            }

            // Heading
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $m)) {
                $blocks[] = self::heading(strlen($m[1]), self::inline($m[2]));
                $i++;
                continue;
            }

            // Horizontal rule
            if (preg_match('/^([-*_])\1{2,}$/', $trimmed)) {
                $blocks[] = self::separator();
                $i++;
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s?/', $trimmed)) {
                $quote = [];
                while ($i < $n && preg_match('/^>\s?(.*)$/', trim($lines[$i]), $mm)) {
                    $quote[] = $mm[1];
                    $i++;
                }
                $blocks[] = self::quote(self::parse($quote));
                continue;
            }

            // Unordered list
            if (preg_match('/^[-*+]\s+/', $trimmed)) {
                $items = [];
                while ($i < $n && preg_match('/^[-*+]\s+(.*)$/', trim($lines[$i]), $mm)) {
                    $items[] = self::inline($mm[1]);
                    $i++;
                }
                $blocks[] = self::list($items, false);
                continue;
            }

            // Ordered list
            if (preg_match('/^\d+\.\s+/', $trimmed)) {
                $items = [];
                while ($i < $n && preg_match('/^\d+\.\s+(.*)$/', trim($lines[$i]), $mm)) {
                    $items[] = self::inline($mm[1]);
                    $i++;
                }
                $blocks[] = self::list($items, true);
                continue;
            }

            // Paragraph
            $para = [];
            while ($i < $n) {
                $l = trim($lines[$i]);
                if ($l === '' || preg_match('#^(:::|```|\#{1,6}\s|>\s?|[-*+]\s|\d+\.\s)#', $l) || preg_match('/^([-*_])\1{2,}$/', $l)) {
                    break;
                }
                $para[] = $l;
                $i++;
            }
            if ($para !== []) {
                $blocks[] = self::paragraph(self::inline(implode(' ', $para)));
            }
        }

        return implode("\n\n", $blocks);
    }

    private static function paragraph(string $html): string
    {
        return "<!-- wp:paragraph -->\n<p>{$html}</p>\n<!-- /wp:paragraph -->";
    }

    private static function heading(int $level, string $html): string
    {
        $level = max(2, min(6, $level)); // h1 is reserved for the post title
        return "<!-- wp:heading {\"level\":{$level}} -->\n<h{$level}>{$html}</h{$level}>\n<!-- /wp:heading -->";
    }

    private static function separator(): string
    {
        return "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->";
    }

    private static function code(string $code): string
    {
        $esc = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>{$esc}</code></pre>\n<!-- /wp:code -->";
    }

    private static function quote(string $innerBlocks): string
    {
        return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">{$innerBlocks}</blockquote>\n<!-- /wp:quote -->";
    }

    /**
     * @param  list<string>  $items
     */
    private static function list(array $items, bool $ordered): string
    {
        $tag = $ordered ? 'ol' : 'ul';
        $attr = $ordered ? ' {"ordered":true}' : '';
        $li = '';
        foreach ($items as $item) {
            $li .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->\n";
        }

        return "<!-- wp:list{$attr} -->\n<{$tag}>\n{$li}</{$tag}>\n<!-- /wp:list -->";
    }

    private static function directive(string $name, string $innerBlocks): string
    {
        $class = 'fn-' . preg_replace('/[^a-z0-9-]/', '', $name);
        $label = self::DIRECTIVES[$name] ?? ucfirst($name);
        $heading = $label !== ''
            ? "<!-- wp:heading {\"level\":4,\"className\":\"fn-callout-label\"} -->\n<h4 class=\"fn-callout-label\">{$label}</h4>\n<!-- /wp:heading -->\n"
            : '';

        return "<!-- wp:group {\"className\":\"{$class}\"} -->\n<div class=\"wp-block-group {$class}\">{$heading}{$innerBlocks}</div>\n<!-- /wp:group -->";
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // [text](url) — restrict to safe schemes
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function (array $m): string {
            $url = preg_match('#^(https?:|mailto:|/)#i', $m[2]) ? $m[2] : '';
            return $url !== '' ? '<a href="'.$url.'">'.$m[1].'</a>' : $m[1];
        }, $text) ?? $text;

        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;

        return $text;
    }
}

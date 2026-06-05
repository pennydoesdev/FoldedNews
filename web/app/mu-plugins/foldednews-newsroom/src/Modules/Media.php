<?php

namespace FoldedNews\Newsroom\Modules;

use FoldedNews\Newsroom\Media\Config;
use FoldedNews\Newsroom\Media\Log;
use FoldedNews\Newsroom\Media\Offloader;
use FoldedNews\Newsroom\Media\S3Client;
use FoldedNews\Newsroom\Media\Signer;
use FoldedNews\Newsroom\Media\UrlRewriter;
use FoldedNews\Newsroom\Module;

/**
 * Wires universal S3-compatible media offload. Inert unless S3 + CDN are
 * configured (so uploads never break without credentials).
 */
final class Media implements Module
{
    public function register(): void
    {
        $config = new Config();

        if (! $config->enabled()) {
            return;
        }

        $offloader = new Offloader($config, new S3Client($config, new Signer()), new Log());
        $rewriter = new UrlRewriter($config);

        // Offload original + generated sizes once WordPress has created them.
        add_filter('wp_generate_attachment_metadata', [$offloader, 'offload'], 20, 2);
        add_action('fn_s3_retry', [$offloader, 'retry']);

        // Serve offloaded media from the CDN. wp_get_attachment_url is the
        // canonical source for REST, RSS, OpenGraph and Sage helpers.
        add_filter('wp_get_attachment_url', [$rewriter, 'attachmentUrl'], 20, 2);
        add_filter('wp_calculate_image_srcset', [$rewriter, 'srcset'], 20, 5);
    }
}

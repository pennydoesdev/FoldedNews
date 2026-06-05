<?php

namespace FoldedNews\Newsroom\Media;

/**
 * Rewrites attachment URLs (and srcset sources) to the CDN, but only for
 * attachments that have a verified offload. Non-offloaded media keeps serving
 * locally, so a failed offload is a safe no-op rather than a broken image.
 *
 * wp_get_attachment_url is the canonical source for REST, RSS enclosures,
 * OpenGraph and Sage helpers, so filtering it covers those paths centrally.
 */
final class UrlRewriter
{
    public function __construct(private readonly Config $config) {}

    public function attachmentUrl(string $url, int $attachmentId): string
    {
        return $this->isOffloaded($attachmentId) ? $this->toCdn($url) : $url;
    }

    /**
     * @param  mixed  $sources
     * @return mixed
     */
    public function srcset($sources, mixed $sizeArray = null, mixed $imageSrc = null, mixed $imageMeta = null, int $attachmentId = 0)
    {
        if (! is_array($sources) || ! $this->isOffloaded($attachmentId)) {
            return $sources;
        }

        foreach ($sources as $key => $source) {
            if (is_array($source) && isset($source['url']) && is_string($source['url'])) {
                $sources[$key]['url'] = $this->toCdn($source['url']);
            }
        }

        return $sources;
    }

    private function isOffloaded(int $attachmentId): bool
    {
        return $attachmentId > 0 && (bool) get_post_meta($attachmentId, '_fn_s3_offloaded', true);
    }

    private function toCdn(string $url): string
    {
        $uploads = wp_get_upload_dir();
        $baseUrl = is_array($uploads) && isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';

        if ($baseUrl === '' || ! str_starts_with($url, $baseUrl)) {
            return $url;
        }

        $relative = ltrim(substr($url, strlen($baseUrl)), '/');
        $prefix = $this->config->prefix();
        $path = $prefix === '' ? $relative : "{$prefix}/{$relative}";

        return $this->config->cdnUrl().'/'.$path;
    }
}

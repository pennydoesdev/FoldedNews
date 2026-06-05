<?php

namespace FoldedNews\Newsroom\Media;

/**
 * Offloads an attachment's original + generated sizes to S3, verifies each
 * remote object, then (optionally) deletes the local copies. Any failure keeps
 * the local files and schedules a retry, so the site degrades gracefully.
 */
final class Offloader
{
    public function __construct(
        private readonly Config $config,
        private readonly S3Client $client,
        private readonly Log $log
    ) {}

    /**
     * Filter callback for `wp_generate_attachment_metadata`.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function offload(array $metadata, int $attachmentId): array
    {
        $file = get_attached_file($attachmentId);
        $uploads = wp_get_upload_dir();
        $basedir = is_array($uploads) && isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';

        if (! is_string($file) || $file === '' || ! is_file($file) || $basedir === '') {
            return $metadata;
        }

        $files = $this->collectFiles($file, $metadata, $basedir);
        $failed = [];

        foreach ($files as $relative => $absolute) {
            $size = is_file($absolute) ? (int) filesize($absolute) : 0;

            if ($size > $this->config->multipartThreshold()) {
                // Single-part upload would buffer the whole file in memory.
                $this->log->record($attachmentId, ["{$relative} (exceeds multipart threshold; multipart is a follow-up)"]);
                return $metadata;
            }

            if (! $this->uploadAndVerify($relative, $absolute)) {
                $failed[] = $relative;
            }
        }

        if ($failed !== []) {
            $this->log->record($attachmentId, $failed);
            $this->scheduleRetry($attachmentId);

            return $metadata; // keep local files + local URLs
        }

        update_post_meta($attachmentId, '_fn_s3_offloaded', 1);

        if ($this->config->deleteLocal()) {
            foreach ($files as $absolute) {
                if (is_file($absolute)) {
                    @unlink($absolute);
                }
            }
        }

        return $metadata;
    }

    /**
     * Retry handler for the `fn_s3_retry` cron event.
     */
    public function retry(int $attachmentId): void
    {
        $metadata = wp_get_attachment_metadata($attachmentId);
        $this->offload(is_array($metadata) ? $metadata : [], $attachmentId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, string>  relative path => absolute path
     */
    private function collectFiles(string $file, array $metadata, string $basedir): array
    {
        $relativeOriginal = ltrim(str_replace($basedir, '', $file), '/');
        $dir = dirname($relativeOriginal);
        $files = [$relativeOriginal => $file];

        $sizes = $metadata['sizes'] ?? null;
        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                if (is_array($size) && isset($size['file']) && is_string($size['file'])) {
                    $relative = ($dir === '.' ? '' : $dir.'/').$size['file'];
                    $files[$relative] = $basedir.'/'.$relative;
                }
            }
        }

        return $files;
    }

    private function uploadAndVerify(string $relative, string $absolute): bool
    {
        if (! is_file($absolute)) {
            return false;
        }

        $body = file_get_contents($absolute);
        if ($body === false) {
            return false;
        }

        $type = wp_check_filetype($absolute);
        $contentType = is_string($type['type'] ?? null) ? $type['type'] : 'application/octet-stream';

        $key = $this->key($relative);

        return $this->client->putObject($key, $body, $contentType)
            && $this->client->objectExists($key);
    }

    private function key(string $relative): string
    {
        $prefix = $this->config->prefix();

        return $prefix === '' ? $relative : "{$prefix}/{$relative}";
    }

    private function scheduleRetry(int $attachmentId): void
    {
        if (! wp_next_scheduled('fn_s3_retry', [$attachmentId])) {
            wp_schedule_single_event(time() + 300, 'fn_s3_retry', [$attachmentId]);
        }
    }
}

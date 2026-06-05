<?php

namespace FoldedNews\Newsroom\Media;

/**
 * S3 / CDN media-offload configuration, read from environment variables
 * (Bedrock .env). Offload stays inert unless fully configured.
 */
final class Config
{
    public function endpoint(): string
    {
        return $this->str('S3_ENDPOINT');
    }

    public function region(): string
    {
        return $this->str('S3_REGION') ?: 'us-east-1';
    }

    public function bucket(): string
    {
        return $this->str('S3_BUCKET');
    }

    public function key(): string
    {
        return $this->str('S3_KEY');
    }

    public function secret(): string
    {
        return $this->str('S3_SECRET');
    }

    public function prefix(): string
    {
        return trim($this->str('S3_PATH_PREFIX'), '/');
    }

    public function cdnUrl(): string
    {
        return rtrim($this->str('CDN_URL'), '/');
    }

    public function forceHttps(): bool
    {
        return $this->bool('S3_FORCE_HTTPS', true);
    }

    public function deleteLocal(): bool
    {
        return $this->bool('S3_DELETE_LOCAL', false);
    }

    public function signedUrls(): bool
    {
        return $this->bool('S3_SIGNED_URLS', false);
    }

    public function cacheControl(): string
    {
        return $this->str('S3_CACHE_CONTROL') ?: 'public, max-age=31536000, immutable';
    }

    public function multipartThreshold(): int
    {
        return $this->int('S3_MULTIPART_THRESHOLD', 100 * 1024 * 1024);
    }

    /**
     * Offload only runs when storage and the public CDN URL are configured.
     */
    public function enabled(): bool
    {
        return $this->endpoint() !== ''
            && $this->bucket() !== ''
            && $this->key() !== ''
            && $this->secret() !== ''
            && $this->cdnUrl() !== '';
    }

    private function str(string $name): string
    {
        $value = getenv($name);

        return is_string($value) ? trim($value) : '';
    }

    private function bool(string $name, bool $default): bool
    {
        $value = getenv($name);

        if (! is_string($value) || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }

    private function int(string $name, int $default): int
    {
        $value = getenv($name);

        return is_string($value) && is_numeric($value) ? (int) $value : $default;
    }
}

<?php

namespace FoldedNews\Newsroom\Media;

/**
 * Minimal S3-compatible client (path-style) over the WordPress HTTP API.
 * Single-part PUT + HEAD verify. Multipart for very large files is a follow-up.
 */
final class S3Client
{
    public function __construct(
        private readonly Config $config,
        private readonly Signer $signer
    ) {}

    public function putObject(string $key, string $body, string $contentType): bool
    {
        $status = $this->request('PUT', $key, $body, [
            'content-type' => $contentType,
            'cache-control' => $this->config->cacheControl(),
        ]);

        return $status !== null && $status >= 200 && $status < 300;
    }

    public function objectExists(string $key): bool
    {
        return $this->request('HEAD', $key, '', []) === 200;
    }

    /**
     * @param  array<string, string>  $extraHeaders
     * @return int|null  HTTP status, or null on transport error
     */
    private function request(string $method, string $key, string $body, array $extraHeaders): ?int
    {
        $endpoint = $this->config->endpoint();
        $host = (string) parse_url($endpoint, PHP_URL_HOST);
        if ($host === '') {
            return null;
        }

        $path = '/'.$this->config->bucket().'/'.$this->encodeKey($key);
        $amzDate = gmdate('Ymd\THis\Z');
        $payloadHash = hash('sha256', $body);

        $headers = array_merge([
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
        ], $extraHeaders);

        $headers['Authorization'] = $this->signer->authorization(
            $method,
            $path,
            '',
            $headers,
            $payloadHash,
            $this->config->region(),
            's3',
            $this->config->key(),
            $this->config->secret(),
            $amzDate
        );

        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'redirection' => 0,
        ];
        if ($method === 'PUT') {
            $args['body'] = $body;
        }

        $response = wp_remote_request(rtrim($endpoint, '/').$path, $args);
        if (is_wp_error($response)) {
            return null;
        }

        return (int) wp_remote_retrieve_response_code($response);
    }

    private function encodeKey(string $key): string
    {
        return implode('/', array_map('rawurlencode', explode('/', ltrim($key, '/'))));
    }
}

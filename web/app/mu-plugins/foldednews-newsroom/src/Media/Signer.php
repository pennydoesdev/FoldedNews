<?php

namespace FoldedNews\Newsroom\Media;

/**
 * AWS Signature Version 4 signer (pure PHP, no SDK, no WordPress).
 *
 * Works with any S3-compatible provider (AWS S3, Cloudflare R2, Backblaze B2,
 * DigitalOcean Spaces, MinIO, Wasabi). Verified against AWS's published
 * SigV4 "get-vanilla" test vector.
 *
 * @link https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
 */
final class Signer
{
    /**
     * Build the Authorization header value for a request.
     *
     * @param  array<string, string>  $headers  Must include host and x-amz-date.
     * @param  string  $uri  Canonical (already RFC3986-encoded) path, e.g. "/bucket/key".
     */
    public function authorization(
        string $method,
        string $uri,
        string $query,
        array $headers,
        string $payloadHash,
        string $region,
        string $service,
        string $accessKey,
        string $secretKey,
        string $amzDate
    ): string {
        $date = substr($amzDate, 0, 8);

        $canonical = [];
        foreach ($headers as $name => $value) {
            $canonical[strtolower(trim($name))] = trim($value);
        }
        ksort($canonical);

        $canonicalHeaders = '';
        foreach ($canonical as $name => $value) {
            $canonicalHeaders .= "{$name}:{$value}\n";
        }
        $signedHeaders = implode(';', array_keys($canonical));

        $canonicalRequest = implode("\n", [
            strtoupper($method),
            $uri === '' ? '/' : $uri,
            $query,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = "{$date}/{$region}/{$service}/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate = hash_hmac('sha256', $date, "AWS4{$secretKey}", true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $accessKey,
            $scope,
            $signedHeaders,
            $signature
        );
    }
}

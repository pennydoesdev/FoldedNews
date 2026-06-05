# Media Offload

> Status: skeleton. Populated in **Stage 5 (Universal S3-compatible offload)**.

## Principle

Local WordPress uploads are temporary; S3-compatible object storage is
canonical; the configured CDN/media URL is the public source. No public
`web/app/uploads/` (or vanilla `wp-content/uploads/`) URLs in markup.

## Adapter

- **Official docs:** AWS S3 API https://docs.aws.amazon.com/AmazonS3/latest/API/ ·
  S3-compatible providers (Cloudflare R2, Backblaze B2, MinIO, DigitalOcean
  Spaces, Wasabi) expose the same API.
- **SDK / package:** `aws/aws-sdk-php` (S3 client) — https://docs.aws.amazon.com/sdk-for-php/
- **Auth:** access key + secret (signature v4)
- **Required env vars:** `S3_ENDPOINT`, `S3_REGION`, `S3_BUCKET`, `S3_KEY`,
  `S3_SECRET`, `S3_PATH_PREFIX`, `CDN_URL`, `S3_FORCE_HTTPS`,
  `S3_DELETE_LOCAL`, `S3_SIGNED_URLS`, `S3_CACHE_CONTROL`, `S3_MULTIPART_THRESHOLD`
- **Webhook docs:** n/a
- **Rate limits:** provider-specific (see each provider's docs)
- **Testing:** MinIO locally (https://min.io/docs/) or provider test bucket;
  upload image/video/audio/PDF and assert the rewritten URL is the CDN and the
  remote object exists.

## Behaviour (Stage 5)

Offload original + generated sizes → verify remote object → rewrite all URLs
(attachments, srcset, REST, RSS, podcast enclosures, OpenGraph, Sage helpers) →
optionally delete local after verification → retry + log failures. Image
optimization: responsive sizes, WebP/AVIF, lazy loading, width/height,
lossless option preserving originals.

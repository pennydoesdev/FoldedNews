<?php

namespace FoldedNews\Newsroom\Media;

/**
 * Records media-offload failures (capped ring buffer in an option, plus the
 * PHP error log). Surfaced by the Stage 18 System Health dashboard.
 */
final class Log
{
    private const OPTION = 'fn_s3_failures';

    /**
     * @param  list<string>  $files
     */
    public function record(int $attachmentId, array $files): void
    {
        $entries = get_option(self::OPTION, []);
        if (! is_array($entries)) {
            $entries = [];
        }

        $entries[] = [
            'id' => $attachmentId,
            'files' => $files,
            'time' => time(),
        ];

        update_option(self::OPTION, array_slice($entries, -100), false);

        error_log(sprintf(
            '[foldednews-media] offload failed for attachment %d: %s',
            $attachmentId,
            implode(', ', $files)
        ));
    }
}

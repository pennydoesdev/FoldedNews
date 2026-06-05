<?php

/**
 * Constants WordPress defines at runtime (wp-config / wp-load), declared here so
 * static analysis knows they exist. Their values are treated as dynamic via the
 * `dynamicConstantNames` parameter. Not loaded by the application.
 */
defined('ABSPATH') || define('ABSPATH', '/wp/');
defined('WPINC') || define('WPINC', 'wp-includes');

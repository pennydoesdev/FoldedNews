<?php

/**
 * Configuration overrides for WP_ENV === 'staging'
 * Mirrors production but keeps search engines out.
 */

use Roots\WPConfig\Config;

Config::define('DISALLOW_INDEXING', true);
Config::define('WP_DEBUG', false);
Config::define('WP_DEBUG_DISPLAY', false);

<?php

it('ships a Bedrock-compatible web root config', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/config/application.php'))
        ->toContain("Config::define('CONTENT_DIR', '/app')")
        ->toContain("define('ABSPATH'");
});

it('boots Acorn from the theme', function () {
    expect(file_get_contents(dirname(__DIR__, 2).'/web/app/themes/foldednews/functions.php'))
        ->toContain('Roots\Acorn\Application')
        ->toContain('->boot()');
});

<?php

use FoldedNews\Newsroom\Modules\ContentTypes;
use FoldedNews\Newsroom\Modules\Taxonomies;

$base = dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom';
require_once $base.'/src/Module.php';
require_once $base.'/src/Support/Labels.php';
require_once $base.'/src/Modules/ContentTypes.php';
require_once $base.'/src/Modules/Taxonomies.php';

it('registers the full newsroom content-type set', function () {
    $definitions = (new ReflectionMethod(ContentTypes::class, 'definitions'));
    $definitions->setAccessible(true);
    $types = $definitions->invoke(new ContentTypes());

    expect($types)->toHaveCount(15)
        ->toHaveKeys(['fn_article', 'fn_live_blog', 'fn_person', 'fn_correction', 'fn_source_note']);
});

it('defines the ten article formats', function () {
    $formats = (new ReflectionClass(Taxonomies::class))->getConstant('FORMATS');

    expect($formats)->toBeArray()->toHaveCount(10)
        ->toContain('Breaking News', 'Investigation', 'Live Article');
});

<?php

use FoldedNews\Newsroom\Support\MarkdownToBlocks;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Support/MarkdownToBlocks.php';

it('converts markdown to Gutenberg block markup', function () {
    $out = MarkdownToBlocks::convert("# Hi\n\nA **bold** word.\n\n- a\n- b");

    expect($out)
        ->toContain('<!-- wp:heading {"level":2} -->')   // h1 bumped to h2 (title owns h1)
        ->toContain('<strong>bold</strong>')
        ->toContain('<!-- wp:list-item -->');
});

it('maps ::: directives to group blocks with a label', function () {
    $out = MarkdownToBlocks::convert(":::note\nHello\n:::");

    expect($out)
        ->toContain('wp:group {"className":"fn-note"}')
        ->toContain('<h4 class="fn-callout-label">Note</h4>')
        ->toContain('<p>Hello</p>');
});

it('escapes raw HTML in source', function () {
    expect(MarkdownToBlocks::convert('a <script>x</script> b'))
        ->not->toContain('<script>');
});

it('emits interactive viz figures for map/chart/diagram', function () {
    $out = MarkdownToBlocks::convert(":::chart\n{\"type\":\"bar\"}\n:::");

    expect($out)
        ->toContain('<!-- wp:html -->')
        ->toContain('data-fn-viz="chart"')
        ->toContain('class="fn-viz-mount"');

    preg_match('/data-fn-config="([^"]+)"/', $out, $m);
    expect(base64_decode($m[1]))->toContain('"type":"bar"');
});

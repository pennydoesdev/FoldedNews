<?php

use FoldedNews\Newsroom\Media\Signer;

require_once dirname(__DIR__, 2).'/web/app/mu-plugins/foldednews-newsroom/src/Media/Signer.php';

it('matches the AWS SigV4 get-vanilla test vector', function () {
    $auth = (new Signer())->authorization(
        'GET',
        '/',
        '',
        ['host' => 'example.amazonaws.com', 'x-amz-date' => '20150830T123600Z'],
        hash('sha256', ''),
        'us-east-1',
        'service',
        'AKIDEXAMPLE',
        'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY',
        '20150830T123600Z',
    );

    expect($auth)
        ->toContain('Credential=AKIDEXAMPLE/20150830/us-east-1/service/aws4_request')
        ->toContain('SignedHeaders=host;x-amz-date')
        ->toContain('Signature=5fa00fa31553b73ebf1942676e86291e8372ff2a2260956d9b8aae1d763fbf31');
});

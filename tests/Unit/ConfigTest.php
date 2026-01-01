<?php

use Nextvisit\ClaimMDWrapper\Config;

describe('Config', function () {
    it('returns the correct base URI', function () {
        $config = new Config();

        expect($config->getBaseUri())->toBe('https://svc.claim.md/');
    });

    it('returns a string for base URI', function () {
        $config = new Config();

        expect($config->getBaseUri())->toBeString();
    });
});

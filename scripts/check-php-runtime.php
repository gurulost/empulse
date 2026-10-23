<?php

use App\Support\RuntimeSecurityPolicy;

// Run before Composer/Laravel bootstrap so an old build cannot serve requests.
require_once __DIR__.'/../app/Support/RuntimeSecurityPolicy.php';

if (! RuntimeSecurityPolicy::supportsPhp(PHP_VERSION)) {
    fwrite(STDERR, 'Unsupported PHP runtime '.PHP_VERSION.'. Require PHP 8.2.35, 8.3.36, 8.4.27, or 8.5.12 or a later patch on those branches.'.PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'PHP runtime security floor passed: '.PHP_VERSION.PHP_EOL);

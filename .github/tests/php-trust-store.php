<?php

$locations = openssl_get_cert_locations();
$bundle = getenv($locations['default_cert_file_env'])
    ?: ($locations['ini_cafile'] ?: $locations['default_cert_file']);
if (! is_file($bundle)) {
    throw new RuntimeException('Active OpenSSL CA bundle is unavailable.');
}
$files = [$bundle];
$directories = getenv($locations['default_cert_dir_env']) ?: $locations['default_cert_dir'];
foreach (explode(PATH_SEPARATOR, $directories) as $directory) {
    if (! is_dir($directory)) {
        continue;
    }
    foreach (new DirectoryIterator($directory) as $file) {
        if ($file->isFile() && is_readable($file->getPathname())) {
            $files[] = $file->getPathname();
        }
    }
}
$roots = [];
foreach (array_unique($files) as $path) {
    preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', file_get_contents($path), $matches);
    foreach ($matches[0] as $pem) {
        $certificate = openssl_x509_parse($pem);
        if ($certificate === false) {
            throw new RuntimeException('Unable to parse an active trust certificate.');
        }
        $name = $certificate['subject']['CN'] ?? '';
        if (in_array($name, ['emSign Root CA - C1', 'emSign Root CA - C3', 'emSign Root CA - G3'], true)) {
            throw new RuntimeException('Affected emSign root remains in the active OpenSSL trust store.');
        }
        $roots[openssl_x509_fingerprint($pem, 'sha256')] = true;
    }
}
if (count($roots) < 100) {
    throw new RuntimeException('Unexpectedly empty or incomplete default trust store.');
}
echo 'Actual PHP OpenSSL bundle/directory checks passed; unique certificates: '.count($roots).PHP_EOL;

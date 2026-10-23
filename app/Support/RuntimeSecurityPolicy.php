<?php

namespace App\Support;

final class RuntimeSecurityPolicy
{
    public static function supportsPhp(string $version): bool
    {
        // October 21, 2026 PHP security floors. New branches require review.
        $minimums = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];
        if (! preg_match('/\A(8\.[2-5])\.\d+\z/', $version, $matches)) {
            return false;
        }

        return version_compare($version, $minimums[$matches[1]], '>=');
    }
}

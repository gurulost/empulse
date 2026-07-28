<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FrontendAssetContractTest extends TestCase
{
    public function test_current_templates_do_not_load_runtime_cdn_assets(): void
    {
        $root = dirname(__DIR__, 2);
        $forbiddenHosts = [
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'fonts.googleapis.com',
            'fonts.gstatic.com',
        ];
        $findings = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                "{$root}/resources/views",
                FilesystemIterator::SKIP_DOTS
            )
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertNotFalse($contents);
            foreach ($forbiddenHosts as $host) {
                if (str_contains($contents, $host)) {
                    $findings[] = $file->getPathname().': '.$host;
                }
            }
        }

        self::assertSame(
            [],
            $findings,
            'Current templates must use Vite-bundled styles, fonts, icons, and scripts.'
        );
    }

    public function test_self_hosted_visual_dependencies_and_survey_styles_are_declared(): void
    {
        $root = dirname(__DIR__, 2);
        $package = json_decode(
            (string) file_get_contents("{$root}/package.json"),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $dependencies = $package['dependencies'] ?? [];

        foreach ([
            '@fontsource/dm-sans',
            '@fontsource/inter',
            '@fontsource/outfit',
            'bootstrap-icons',
        ] as $dependency) {
            self::assertArrayHasKey($dependency, $dependencies);
        }

        $surveyView = (string) file_get_contents("{$root}/resources/views/surveys/show.blade.php");
        self::assertStringContainsString(
            "@vite(['resources/sass/app.scss', 'resources/js/app.js'])",
            $surveyView
        );
    }
}

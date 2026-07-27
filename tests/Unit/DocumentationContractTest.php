<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DocumentationContractTest extends TestCase
{
    public function test_canonical_documentation_is_portable_and_historical_records_are_isolated(): void
    {
        $root = dirname(__DIR__, 2);
        $canonicalDocuments = [
            'docs/PRODUCT_VISION_AND_BUSINESS_MODEL.md',
            'docs/ARCHITECTURE.md',
            'docs/EMPULSE_PRODUCTION_READINESS_CHECKLIST.md',
            'docs/PRODUCTION_DEPLOYMENT_RUNBOOK.md',
            'docs/RELEASE_CANDIDATE_EVIDENCE_2026-07-27.md',
        ];

        $readme = $this->read("{$root}/README.md");
        foreach ($canonicalDocuments as $path) {
            self::assertFileExists("{$root}/{$path}");
            self::assertStringContainsString("]({$path})", $readme);
        }

        $legacyDocuments = [
            'AUDIT.md' => 'LEGACY_CODE_AUDIT.md',
            'DEPLOY_HANDOFF_2026-02-06.md' => 'DEPLOY_HANDOFF_2026-02-06.md',
            'production-demo-readiness-audit-production-checklist.md' => 'production-demo-readiness-audit-production-checklist.md',
            'production-demo-readiness-implementation-production-checklist.md' => 'production-demo-readiness-implementation-production-checklist.md',
            'remaining-gaps-hardening-production-checklist.md' => 'remaining-gaps-hardening-production-checklist.md',
        ];

        foreach ($legacyDocuments as $legacyPath => $archivePath) {
            self::assertFileDoesNotExist("{$root}/docs/{$legacyPath}");
            self::assertFileExists("{$root}/docs/archive/{$archivePath}");
        }

        $archiveIndex = $this->read("{$root}/docs/archive/README.md");
        self::assertStringContainsString('do not establish current product direction', $archiveIndex);
        self::assertStringContainsString('must not be interpreted as permission to deploy', $archiveIndex);

        $nonPortableReferences = [];
        $brokenRelativeLinks = [];
        $absoluteHomePrefix = '/'.'Users/';
        $fileUriPrefix = 'file'.'://';
        foreach ($this->markdownFiles($root) as $path) {
            $contents = $this->read($path);
            if (str_contains($contents, $absoluteHomePrefix) || str_contains($contents, $fileUriPrefix)) {
                $nonPortableReferences[] = substr($path, strlen($root) + 1);
            }

            preg_match_all('/\]\((?<target>[^)]+)\)/', $contents, $matches);
            foreach ($matches['target'] as $target) {
                $target = trim($target, " \t\n\r\0\x0B<>");
                if ($target === '' || str_starts_with($target, '#') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target)) {
                    continue;
                }

                $targetPath = rawurldecode(explode('#', $target, 2)[0]);
                $resolved = dirname($path).DIRECTORY_SEPARATOR.$targetPath;
                if (! file_exists($resolved)) {
                    $brokenRelativeLinks[] = sprintf(
                        '%s -> %s',
                        substr($path, strlen($root) + 1),
                        $target
                    );
                }
            }
        }

        self::assertSame([], $nonPortableReferences, 'Documentation contains machine-specific file references.');
        self::assertSame([], $brokenRelativeLinks, 'Documentation contains broken relative links.');
    }

    /**
     * @return list<string>
     */
    private function markdownFiles(string $root): array
    {
        $paths = ["{$root}/README.md"];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                "{$root}/docs",
                FilesystemIterator::SKIP_DOTS
            )
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertNotFalse($contents, "Unable to read {$path}.");

        return $contents;
    }
}

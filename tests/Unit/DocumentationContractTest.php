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
            'docs/LAUNCH_DECISIONS_AND_EXTERNAL_REVIEW_HANDOFF.md',
            'docs/AUDIT_BACKLOG_TRACEABILITY_2026-07-27.md',
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

        $traceability = $this->read("{$root}/docs/AUDIT_BACKLOG_TRACEABILITY_2026-07-27.md");
        preg_match_all('/^\| (EMP-\d{3}) \|/m', $traceability, $ticketMatches);
        $expectedTickets = ['EMP-000'];
        foreach ([
            [1, 15],
            [101, 114],
            [201, 215],
            [301, 310],
            [401, 410],
            [501, 510],
        ] as [$first, $last]) {
            for ($number = $first; $number <= $last; $number++) {
                $expectedTickets[] = sprintf('EMP-%03d', $number);
            }
        }

        self::assertCount(75, $ticketMatches[1], 'Traceability must contain all 75 audit tickets.');
        self::assertSame(
            $expectedTickets,
            $ticketMatches[1],
            'Traceability tickets must be complete, unique, and in canonical order.'
        );

        $launchHandoff = $this->read("{$root}/docs/LAUNCH_DECISIONS_AND_EXTERNAL_REVIEW_HANDOFF.md");
        self::assertStringContainsString('Status: owner decisions and external evidence pending', $launchHandoff);
        self::assertStringContainsString('Empulse has not been deployed, sold, or approved for customer use.', $launchHandoff);
        self::assertStringContainsString('Automated axe and browser checks are supporting evidence, not this independent human approval.', $launchHandoff);
        self::assertStringContainsString('Git history was intentionally preserved', $launchHandoff);
        self::assertStringContainsString('remains visible in old commits', $launchHandoff);
        self::assertStringContainsString('Do not mark provider, reviewer, commercial, or deployment gates complete from repository tests alone.', $launchHandoff);
        self::assertStringContainsString('Build/image digest | Pending provider/build selection', $launchHandoff);
        self::assertStringContainsString('No design-partner employee data is admitted to staging.', $launchHandoff);
        self::assertStringContainsString('Execute production as a separate approved change record', $launchHandoff);

        $decisionLedger = explode('### Decision detail', explode('### Decision ledger', $launchHandoff, 2)[1], 2)[0];
        preg_match_all('/^\| (LD-\d{3}) \|/m', $decisionLedger, $decisionMatches);
        self::assertSame(
            array_map(
                static fn (int $number): string => sprintf('LD-%03d', $number),
                range(1, 11)
            ),
            $decisionMatches[1],
            'Launch decision ledger must contain LD-001 through LD-011 exactly once and in order.'
        );
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

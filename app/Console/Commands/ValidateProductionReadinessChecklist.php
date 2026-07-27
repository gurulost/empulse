<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateProductionReadinessChecklist extends Command
{
    protected $signature = 'readiness:checklist
        {path=docs/EMPULSE_PRODUCTION_READINESS_CHECKLIST.md : Repository-relative or absolute checklist path}
        {--require-signoff : Fail unless every checklist item is checked and terminal}';

    protected $description = 'Validate production-readiness checklist structure and optional final sign-off.';

    /**
     * @var list<string>
     */
    private const ALLOWED_STATUSES = [
        'open',
        'in_progress',
        'verified',
        'accepted_risk',
        'blocked',
    ];

    /**
     * @var list<string>
     */
    private const TERMINAL_STATUSES = [
        'verified',
        'accepted_risk',
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Checklist is not readable: {$path}");

            return self::FAILURE;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->error("Checklist could not be read: {$path}");

            return self::FAILURE;
        }

        [$items, $failures] = $this->parseItems($lines);

        if ($items === []) {
            $failures[] = 'No checklist items were found.';
        }

        if ((bool) $this->option('require-signoff')) {
            foreach ($items as $item) {
                if (! $item['checked'] || ! in_array($item['status'], self::TERMINAL_STATUSES, true)) {
                    $failures[] = sprintf(
                        '%s on line %d is not signed off (checked=%s, status=%s).',
                        $item['id'],
                        $item['line'],
                        $item['checked'] ? 'yes' : 'no',
                        $item['status'],
                    );
                }
            }
        }

        if ($failures !== []) {
            $this->error(
                (bool) $this->option('require-signoff')
                    ? 'Checklist sign-off is not ready.'
                    : 'Checklist validation failed.'
            );

            foreach ($failures as $failure) {
                $this->line(' - '.$failure);
            }

            return self::FAILURE;
        }

        $counts = array_count_values(array_column($items, 'status'));
        ksort($counts);

        $summary = collect($counts)
            ->map(fn (int $count, string $status): string => "{$status}={$count}")
            ->implode(', ');

        $this->info(sprintf(
            'Checklist %s passed: %d items (%s).',
            (bool) $this->option('require-signoff') ? 'sign-off' : 'structure',
            count($items),
            $summary,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $lines
     * @return array{
     *     0: list<array{id: string, checked: bool, status: string, line: int}>,
     *     1: list<string>
     * }
     */
    private function parseItems(array $lines): array
    {
        $items = [];
        $failures = [];
        $seen = [];

        foreach ($lines as $index => $line) {
            if (! str_starts_with($line, '- [')) {
                continue;
            }

            $lineNumber = $index + 1;
            if (! preg_match(
                '/^- \[(?<checked>[ xX])\] (?<id>[A-Z]+-\d{3}) \[status:(?<status>[a-z_]+)\](?:\s+.+)$/',
                $line,
                $matches
            )) {
                $failures[] = "Malformed checklist item on line {$lineNumber}.";

                continue;
            }

            $id = $matches['id'];
            $status = $matches['status'];
            $checked = strtolower($matches['checked']) === 'x';

            if (isset($seen[$id])) {
                $failures[] = "{$id} is duplicated on lines {$seen[$id]} and {$lineNumber}.";
            } else {
                $seen[$id] = $lineNumber;
            }

            if (! in_array($status, self::ALLOWED_STATUSES, true)) {
                $failures[] = "{$id} on line {$lineNumber} has unsupported status {$status}.";
            }

            $terminal = in_array($status, self::TERMINAL_STATUSES, true);
            if ($checked !== $terminal) {
                $failures[] = sprintf(
                    '%s on line %d has inconsistent checkbox/status (checked=%s, status=%s).',
                    $id,
                    $lineNumber,
                    $checked ? 'yes' : 'no',
                    $status,
                );
            }

            if ($status === 'accepted_risk') {
                $details = $this->detailBlockAfter($lines, $index);
                if (! preg_match('/^[ \t]{2,}- Rationale:[ \t]*\S.*$/m', $details)) {
                    $failures[] = "{$id} accepted risk is missing a Rationale detail.";
                }
                if (! preg_match('/^[ \t]{2,}- Owner:[ \t]*\S.*$/m', $details)) {
                    $failures[] = "{$id} accepted risk is missing an Owner detail.";
                }
            }

            $items[] = [
                'id' => $id,
                'checked' => $checked,
                'status' => $status,
                'line' => $lineNumber,
            ];
        }

        return [$items, $failures];
    }

    /**
     * @param  list<string>  $lines
     */
    private function detailBlockAfter(array $lines, int $itemIndex): string
    {
        $details = [];

        for ($index = $itemIndex + 1; $index < count($lines); $index++) {
            $line = $lines[$index];
            if (str_starts_with($line, '- [') || str_starts_with($line, '#')) {
                break;
            }

            $details[] = $line;
        }

        return implode("\n", $details);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}

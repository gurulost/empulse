<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionReadinessChecklistCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_checklist_structure_is_valid(): void
    {
        $this->artisan('readiness:checklist')
            ->expectsOutputToContain('Checklist structure passed')
            ->assertSuccessful();
    }

    public function test_current_checklist_refuses_final_signoff_while_gates_are_open(): void
    {
        $this->artisan('readiness:checklist', ['--require-signoff' => true])
            ->expectsOutputToContain('Checklist sign-off is not ready.')
            ->expectsOutputToContain('G-001')
            ->assertFailed();
    }

    public function test_fully_terminal_checklist_passes_required_signoff(): void
    {
        $path = $this->writeChecklist(<<<'MARKDOWN'
        # Release Checklist

        - [x] G-001 [status:verified] Required validation is complete.
        - [x] R-001 [status:accepted_risk] Provider limitation is accepted.
          - Rationale: The provider owns the remaining external control.
          - Owner: Release owner
        MARKDOWN);

        try {
            $this->artisan('readiness:checklist', [
                'path' => $path,
                '--require-signoff' => true,
            ])
                ->expectsOutputToContain('Checklist sign-off passed: 2 items')
                ->assertSuccessful();
        } finally {
            File::delete($path);
        }
    }

    public function test_duplicate_and_inconsistent_items_fail_closed(): void
    {
        $path = $this->writeChecklist(<<<'MARKDOWN'
        # Release Checklist

        - [ ] G-001 [status:verified] Checkbox conflicts with terminal status.
        - [x] G-001 [status:verified] Duplicate identifier.
        MARKDOWN);

        try {
            $this->artisan('readiness:checklist', ['path' => $path])
                ->expectsOutputToContain('Checklist validation failed.')
                ->expectsOutputToContain('inconsistent checkbox/status')
                ->expectsOutputToContain('duplicated')
                ->assertFailed();
        } finally {
            File::delete($path);
        }
    }

    public function test_malformed_checklist_item_fails_closed(): void
    {
        $path = $this->writeChecklist(<<<'MARKDOWN'
        # Release Checklist

        - [ ] G-001 Missing the required status marker.
        MARKDOWN);

        try {
            $this->artisan('readiness:checklist', ['path' => $path])
                ->expectsOutputToContain('Malformed checklist item')
                ->assertFailed();
        } finally {
            File::delete($path);
        }
    }

    public function test_accepted_risk_requires_rationale_and_owner(): void
    {
        $path = $this->writeChecklist(<<<'MARKDOWN'
        # Release Checklist

        - [x] R-001 [status:accepted_risk] Empty accountable details.
          - Rationale:
          - Owner:
        MARKDOWN);

        try {
            $this->artisan('readiness:checklist', ['path' => $path])
                ->expectsOutputToContain('missing a Rationale')
                ->expectsOutputToContain('missing an Owner')
                ->assertFailed();
        } finally {
            File::delete($path);
        }
    }

    private function writeChecklist(string $contents): string
    {
        $path = storage_path('framework/testing/readiness-checklist-'.bin2hex(random_bytes(8)).'.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents."\n");

        return $path;
    }
}

<?php

namespace App\Services;

use App\Models\InterventionPlaybookVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InterventionPlaybookService
{
    public function publishedForMetric(string $metricId): Collection
    {
        return InterventionPlaybookVersion::query()
            ->where('status', 'published')
            ->orderBy('title')
            ->get()
            ->filter(fn (InterventionPlaybookVersion $playbook): bool => $this->matches($playbook, $metricId))
            ->values();
    }

    public function resolveApplicable(int $playbookVersionId, string $metricId): InterventionPlaybookVersion
    {
        $playbook = InterventionPlaybookVersion::query()
            ->where('status', 'published')
            ->findOrFail($playbookVersionId);

        if (! $this->matches($playbook, $metricId)) {
            throw new \DomainException('The selected intervention playbook is not eligible for this finding.');
        }

        return $playbook;
    }

    private function matches(InterventionPlaybookVersion $playbook, string $metricId): bool
    {
        foreach ($playbook->eligible_metric_patterns ?? [] as $pattern) {
            if (is_string($pattern) && Str::is($pattern, $metricId)) {
                return true;
            }
        }

        return false;
    }
}

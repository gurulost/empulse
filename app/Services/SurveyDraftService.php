<?php

namespace App\Services;

use App\Models\SurveyAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SurveyDraftService
{
    /**
     * @param  array<string, mixed>  $responses
     * @return array{saved: bool, revision: int, last_autosaved_at: Carbon|null}
     */
    public function save(
        SurveyAssignment $assignment,
        array $responses,
        int $expectedRevision
    ): array {
        $savedAt = now();
        $updated = SurveyAssignment::query()
            ->whereKey($assignment->id)
            ->where('status', 'pending')
            ->where('draft_revision', $expectedRevision)
            ->update([
                'draft_answers' => $responses,
                'last_autosaved_at' => $savedAt,
                'draft_revision' => DB::raw('draft_revision + 1'),
                'updated_at' => $savedAt,
            ]);

        if ($updated !== 1) {
            return [
                'saved' => false,
                'revision' => (int) SurveyAssignment::query()
                    ->whereKey($assignment->id)
                    ->value('draft_revision'),
                'last_autosaved_at' => null,
            ];
        }

        return [
            'saved' => true,
            'revision' => $expectedRevision + 1,
            'last_autosaved_at' => $savedAt,
        ];
    }
}

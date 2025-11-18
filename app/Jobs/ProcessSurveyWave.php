<?php

namespace App\Jobs;

use App\Models\SurveyWave;
use App\Models\User;
use App\Services\SurveyService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSurveyWave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(protected int $waveId)
    {
    }

    public function handle(SurveyService $surveyService): void
    {
        $wave = SurveyWave::with('survey')->find($this->waveId);
        if (!$wave || !$wave->survey || !$wave->company_id) {
            return;
        }

        $companyUsers = User::where('company_id', $wave->company_id)->get();
        foreach ($companyUsers as $user) {
            try {
                $assignment = $surveyService->getOrCreateAssignment($user);
                if (!$assignment) {
                    continue;
                }

                $assignment->update([
                    'survey_wave_id' => $wave->id,
                    'wave_label' => $wave->label,
                ]);
            } catch (\Throwable $e) {
                Log::error('Wave scheduling failed', [
                    'wave' => $wave->id,
                    'user' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $wave->update([
            'last_dispatched_at' => now(),
            'status' => $wave->kind === 'full' ? 'completed' : $wave->status,
        ]);

        if ($wave->due_at && $wave->due_at->isPast()) {
            $wave->update(['status' => 'completed']);
        }
    }
}

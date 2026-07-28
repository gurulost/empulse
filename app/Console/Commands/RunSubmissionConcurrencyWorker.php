<?php

namespace App\Console\Commands;

use App\Models\SurveyAssignment;
use App\Services\SurveyAssignmentAccessService;
use App\Services\SurveyDraftService;
use App\Services\SurveyResponseValidationService;
use App\Services\SurveyService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class RunSubmissionConcurrencyWorker extends Command
{
    protected $signature = 'readiness:submission-concurrency-worker
        {operation}
        {assignment_id}
        {payload_path}
        {worker_id}
        {barrier_directory}';

    protected $description = 'Internal worker for the isolated submission concurrency rehearsal.';

    protected $hidden = true;

    public function handle(
        SurveyAssignmentAccessService $access,
        SurveyDraftService $drafts,
        SurveyResponseValidationService $validation,
        SurveyService $surveys
    ): int {
        $workerId = (string) $this->argument('worker_id');
        $barrierDirectory = (string) $this->argument('barrier_directory');
        $resultPath = $barrierDirectory.DIRECTORY_SEPARATOR."{$workerId}.result.json";

        try {
            if (app()->environment('production')) {
                throw new RuntimeException('Concurrency workers refuse to run in production.');
            }

            $operation = (string) $this->argument('operation');
            if (! in_array($operation, ['autosave', 'submit'], true)) {
                throw new RuntimeException('Unsupported concurrency operation.');
            }

            $payload = json_decode(
                File::get((string) $this->argument('payload_path')),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            $assignment = SurveyAssignment::query()
                ->findOrFail((int) $this->argument('assignment_id'));
            $access->assertEligible($assignment);
            $responses = $validation->validateAndSanitize(
                $assignment,
                $payload['responses'] ?? [],
                $operation === 'submit'
            );
            ksort($responses);
            $payloadHash = hash(
                'sha256',
                json_encode($responses, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );

            File::put(
                $barrierDirectory.DIRECTORY_SEPARATOR."{$workerId}.ready",
                now()->toIso8601String()
            );
            $releasePath = $barrierDirectory.DIRECTORY_SEPARATOR.'release';
            $releaseDeadline = microtime(true) + 15;
            while (! File::exists($releasePath) && microtime(true) < $releaseDeadline) {
                usleep(10_000);
            }

            if (! File::exists($releasePath)) {
                throw new RuntimeException('Concurrency worker release barrier timed out.');
            }

            $startedAt = hrtime(true);
            if ($operation === 'autosave') {
                $save = $drafts->save(
                    $assignment,
                    $responses,
                    (int) ($payload['revision'] ?? 0)
                );
                $result = [
                    'worker_id' => $workerId,
                    'status' => $save['saved'] ? 'saved' : 'conflict',
                    'revision' => $save['revision'],
                    'payload_sha256' => $payloadHash,
                    'elapsed_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                ];
            } else {
                try {
                    $response = $surveys->recordResponse($assignment, $responses, [
                        'duration_ms' => $payload['duration_ms'] ?? null,
                    ]);
                    $result = [
                        'worker_id' => $workerId,
                        'status' => 'submitted',
                        'response_id' => $response->id,
                        'payload_sha256' => $payloadHash,
                        'elapsed_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                    ];
                } catch (DomainException $exception) {
                    $result = [
                        'worker_id' => $workerId,
                        'status' => 'conflict',
                        'conflict' => $exception->getMessage(),
                        'payload_sha256' => $payloadHash,
                        'elapsed_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                    ];
                }
            }

            File::put(
                $resultPath,
                json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            File::put(
                $resultPath,
                json_encode([
                    'worker_id' => $workerId,
                    'status' => 'error',
                    'error_class' => $exception::class,
                    'error' => $exception->getMessage(),
                ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );

            return self::FAILURE;
        }
    }
}

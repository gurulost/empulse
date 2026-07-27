<?php

namespace App\Http\Controllers;

use App\Models\OperationalHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'live']);
    }

    public function readiness(): JsonResponse
    {
        try {
            DB::connection()->select('select 1');

            $requiredTables = config('runtime.required_tables', []);
            $missingTables = array_values(array_filter(
                $requiredTables,
                fn (string $table): bool => ! Schema::hasTable($table)
            ));

            if ($missingTables !== []) {
                return response()->json([
                    'status' => 'not_ready',
                    'checks' => [
                        'database' => 'connected',
                        'runtime_tables' => 'missing',
                    ],
                ], 503);
            }

            $processChecks = [];
            if (config('runtime.require_process_heartbeats')) {
                $cutoff = now()->subSeconds((int) config('runtime.heartbeat_max_age_seconds', 180));
                foreach (['scheduler', 'worker'] as $process) {
                    $fresh = OperationalHeartbeat::query()
                        ->where('process', $process)
                        ->where('last_seen_at', '>=', $cutoff)
                        ->exists();
                    $processChecks[$process] = $fresh ? 'fresh' : 'stale';
                }
                if (in_array('stale', $processChecks, true)) {
                    return response()->json([
                        'status' => 'not_ready',
                        'checks' => [
                            'database' => 'connected',
                            'runtime_tables' => 'available',
                            ...$processChecks,
                        ],
                    ], 503);
                }
            }

            return response()->json([
                'status' => 'ready',
                'checks' => [
                    'database' => 'connected',
                    'runtime_tables' => 'available',
                    ...$processChecks,
                ],
            ]);
        } catch (Throwable) {
            return response()->json([
                'status' => 'not_ready',
                'checks' => [
                    'database' => 'unavailable',
                    'runtime_tables' => 'unknown',
                ],
            ], 503);
        }
    }
}

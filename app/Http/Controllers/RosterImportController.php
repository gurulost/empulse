<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\CommitRosterImportRequest;
use App\Http\Requests\StageRosterImportRequest;
use App\Models\Companies;
use App\Models\RosterImport;
use App\Models\User;
use App\Services\RosterImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RosterImportController extends Controller
{
    public function __construct(protected RosterImportService $imports)
    {
        $this->middleware('auth');
    }

    public function store(StageRosterImportRequest $request): JsonResponse
    {
        [$company, $actor] = $this->managerContext($request);
        $result = $this->imports->stage(
            $request->file('file'),
            $company,
            $actor
        );

        return response()->json([
            'data' => $this->imports->summary($result['import']),
            'confirmation_token' => $result['confirmation_token'],
            'queued' => $result['queued'],
            'duplicate' => $result['duplicate'],
        ], $result['queued'] ? 202 : ($result['duplicate'] ? 200 : 201));
    }

    public function show(Request $request, RosterImport $rosterImport): JsonResponse
    {
        $this->managerContext($request, $rosterImport);

        return response()->json([
            'data' => $this->imports->summary($rosterImport->fresh('rows')),
        ]);
    }

    public function issueConfirmationToken(
        Request $request,
        RosterImport $rosterImport
    ): JsonResponse {
        [, $actor] = $this->managerContext($request, $rosterImport);
        $token = $this->imports->issueConfirmationToken($rosterImport, $actor);

        return response()->json([
            'confirmation_token' => $token,
            'expires_at' => $rosterImport->fresh()->confirmation_expires_at?->toISOString(),
        ]);
    }

    public function commit(
        CommitRosterImportRequest $request,
        RosterImport $rosterImport
    ): JsonResponse {
        [, $actor] = $this->managerContext($request, $rosterImport);
        $import = $this->imports->commit(
            $rosterImport,
            (string) $request->validated('confirmation_token'),
            $actor
        );

        return response()->json([
            'message' => 'Roster import committed successfully.',
            'data' => $this->imports->summary($import),
        ]);
    }

    public function result(Request $request, RosterImport $rosterImport): StreamedResponse
    {
        $this->managerContext($request, $rosterImport);
        if ($rosterImport->rows_purged_at) {
            abort(410, 'Detailed roster import rows have expired under the retention policy.');
        }
        $rosterImport->loadMissing('rows');

        return response()->streamDownload(function () use ($rosterImport): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, [
                'row_number',
                'external_id',
                'name',
                'email',
                'role',
                'department',
                'supervisor_external_id',
                'status',
                'action',
                'errors',
            ], ',', '"', '');
            foreach ($rosterImport->rows()->get() as $row) {
                fputcsv($stream, [
                    $row->row_number,
                    $this->safeCsvCell($row->external_id),
                    $this->safeCsvCell($row->name),
                    $this->safeCsvCell($row->email),
                    $row->role,
                    $this->safeCsvCell($row->department),
                    $this->safeCsvCell($row->supervisor_external_id),
                    $row->desired_status,
                    $row->action,
                    $this->safeCsvCell(implode(' | ', $row->errors ?? [])),
                ], ',', '"', '');
            }
            fclose($stream);
        }, "roster-import-{$rosterImport->public_id}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array{Companies, User}
     */
    private function managerContext(Request $request, ?RosterImport $import = null): array
    {
        $actor = $request->user();
        if (! $actor
            || (int) $actor->role !== Role::MANAGER->value
            || ! $actor->company_id) {
            abort(403, 'Only company managers may manage roster imports.');
        }

        $company = Companies::findOrFail($actor->company_id);
        $this->authorize('manageMembers', $company);
        if ($import && (int) $import->company_id !== (int) $company->id) {
            abort(404);
        }

        return [$company, $actor];
    }

    private function safeCsvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}

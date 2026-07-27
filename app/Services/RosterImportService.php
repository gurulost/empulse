<?php

namespace App\Services;

use App\Enums\Role;
use App\Jobs\ParseRosterImport;
use App\Jobs\SendAccountInvitation;
use App\Models\Companies;
use App\Models\RosterExternalIdentity;
use App\Models\RosterImport;
use App\Models\RosterImportRow;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RosterImportService
{
    private const REQUIRED_HEADERS = [
        'external_id',
        'name',
        'email',
        'role',
    ];

    private const OPTIONAL_HEADERS = [
        'department',
        'supervisor_external_id',
        'status',
    ];

    private const MAX_ROWS = 1000;

    private const ASYNC_ROW_THRESHOLD = 100;

    public function __construct(
        protected UserService $users,
        protected OrganizationService $organizations,
        protected AccountInvitationService $accountInvitations,
        protected AuditTrailService $audit
    ) {}

    /**
     * @return array{import: RosterImport, confirmation_token: ?string, queued: bool, duplicate: bool}
     */
    public function stage(UploadedFile $file, Companies $company, User $actor): array
    {
        $path = $file->getRealPath();
        $content = $path ? file_get_contents($path) : false;
        if ($content === false || $content === '') {
            throw ValidationException::withMessages([
                'file' => 'The roster CSV is empty or unreadable.',
            ]);
        }
        if (str_contains($content, "\0") || ! mb_check_encoding($content, 'UTF-8')) {
            throw ValidationException::withMessages([
                'file' => 'The roster CSV must be valid UTF-8 text and may not contain binary data.',
            ]);
        }

        $sourceHash = hash('sha256', $content);
        $existing = RosterImport::query()
            ->where('company_id', $company->id)
            ->where('source_sha256', $sourceHash)
            ->first();
        if ($existing) {
            $token = $existing->status === 'preview_ready'
                ? $this->issueConfirmationToken($existing, $actor)
                : null;

            return [
                'import' => $existing->fresh('rows'),
                'confirmation_token' => $token,
                'queued' => $existing->status === 'parsing',
                'duplicate' => true,
            ];
        }

        $import = RosterImport::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'created_by' => $actor->id,
            'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'source_sha256' => $sourceHash,
            'source_csv' => $content,
            'status' => 'parsing',
        ]);

        $estimatedRows = max(0, substr_count(str_replace("\r\n", "\n", $content), "\n") - 1);
        if ($estimatedRows > self::ASYNC_ROW_THRESHOLD) {
            ParseRosterImport::dispatch($import->id);

            return [
                'import' => $import,
                'confirmation_token' => null,
                'queued' => true,
                'duplicate' => false,
            ];
        }

        $this->parse($import);
        $import->refresh();
        $token = $import->status === 'preview_ready'
            ? $this->issueConfirmationToken($import, $actor)
            : null;

        return [
            'import' => $import->fresh('rows'),
            'confirmation_token' => $token,
            'queued' => false,
            'duplicate' => false,
        ];
    }

    public function parse(RosterImport $import): RosterImport
    {
        $import->refresh();
        if ($import->status !== 'parsing') {
            return $import;
        }

        try {
            $rawRows = $this->readCsv((string) $import->source_csv);
        } catch (\DomainException $exception) {
            $import->update([
                'status' => 'invalid',
                'source_csv' => null,
                'error_count' => 1,
                'failure_summary' => $exception->getMessage(),
                'parsed_at' => now(),
            ]);
            $this->auditPreview($import->fresh());

            return $import->fresh();
        }

        $actor = User::find($import->created_by);
        $departments = DB::table('company_department')
            ->where('company_id', $import->company_id)
            ->pluck('title')
            ->mapWithKeys(fn (string $title): array => [mb_strtolower(trim($title)) => $title]);
        $externalMappings = RosterExternalIdentity::with('user')
            ->where('company_id', $import->company_id)
            ->get()
            ->keyBy('external_id_normalized');
        $emails = collect($rawRows)
            ->pluck('email')
            ->filter()
            ->map(fn ($email): string => mb_strtolower(trim((string) $email)))
            ->unique()
            ->values();
        $emailUsers = User::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->get()
            ->keyBy(fn (User $user): string => mb_strtolower($user->email));

        $externalCounts = collect($rawRows)
            ->pluck('external_id')
            ->map(fn ($value): string => mb_strtolower(trim((string) $value)))
            ->filter()
            ->countBy();
        $emailCounts = collect($rawRows)
            ->pluck('email')
            ->map(fn ($value): string => mb_strtolower(trim((string) $value)))
            ->filter()
            ->countBy();

        $normalized = [];
        foreach ($rawRows as $rawRow) {
            $normalized[] = $this->normalizeRow(
                $rawRow,
                $import,
                $actor,
                $departments,
                $externalMappings,
                $emailUsers,
                $externalCounts,
                $emailCounts
            );
        }

        $byExternalId = collect($normalized)->keyBy(
            fn (array $row): string => mb_strtolower((string) $row['external_id'])
        );
        foreach ($normalized as &$row) {
            $this->validateSupervisor($row, $byExternalId, $externalMappings, $import->company_id);
            if ($row['errors'] !== []) {
                $row['action'] = 'invalid';
            }
        }
        unset($row);

        DB::transaction(function () use ($import, $normalized): void {
            RosterImportRow::where('roster_import_id', $import->id)->delete();
            foreach ($normalized as $row) {
                RosterImportRow::create([
                    'roster_import_id' => $import->id,
                    ...$row,
                ]);
            }

            $counts = collect($normalized)->countBy('action');
            $errorCount = collect($normalized)->filter(fn (array $row): bool => $row['errors'] !== [])->count();
            $import->update([
                'status' => $errorCount > 0 ? 'invalid' : 'preview_ready',
                'source_csv' => null,
                'total_rows' => count($normalized),
                'create_count' => (int) ($counts['create'] ?? 0),
                'update_count' => (int) ($counts['update'] ?? 0),
                'reactivate_count' => (int) ($counts['reactivate'] ?? 0),
                'deactivate_count' => (int) ($counts['deactivate'] ?? 0),
                'unchanged_count' => (int) ($counts['unchanged'] ?? 0),
                'error_count' => $errorCount,
                'failure_summary' => $errorCount > 0
                    ? 'The preview contains row-level errors. Correct the CSV and upload it again.'
                    : null,
                'parsed_at' => now(),
            ]);
        });

        $this->auditPreview($import->fresh());

        return $import->fresh('rows');
    }

    public function issueConfirmationToken(RosterImport $import, User $actor): string
    {
        $this->assertActorCompany($import, $actor);
        if ($import->rows_purged_at || ! $import->rows()->exists()) {
            throw new HttpException(410, 'The detailed roster import preview has expired.');
        }
        if ($import->status !== 'preview_ready' || $import->error_count !== 0) {
            throw new HttpException(409, 'Only an error-free preview can be confirmed.');
        }

        $token = Str::random(64);
        $import->update([
            'confirmation_token_hash' => hash('sha256', $token),
            'confirmation_expires_at' => now()->addMinutes(30),
        ]);

        return $token;
    }

    public function commit(RosterImport $import, string $confirmationToken, User $actor): RosterImport
    {
        $this->assertActorCompany($import, $actor);
        $invitationIds = [];

        $committed = DB::transaction(function () use (
            $import,
            $confirmationToken,
            $actor,
            &$invitationIds
        ): RosterImport {
            $import = RosterImport::query()
                ->whereKey($import->id)
                ->with('rows')
                ->lockForUpdate()
                ->firstOrFail();

            if ($import->status === 'committed') {
                return $import;
            }
            if ($import->status !== 'preview_ready' || $import->error_count !== 0) {
                throw new HttpException(409, 'This roster import is not ready to commit.');
            }
            if (! $import->confirmation_token_hash
                || ! hash_equals($import->confirmation_token_hash, hash('sha256', $confirmationToken))) {
                throw new HttpException(422, 'The roster import confirmation token is invalid.');
            }
            if (! $import->confirmation_expires_at || $import->confirmation_expires_at->isPast()) {
                throw new HttpException(410, 'The roster import confirmation token has expired.');
            }

            $this->assertPreviewIsFresh($import);

            /** @var array<int, User> $resolvedUsers */
            $resolvedUsers = [];
            /** @var array<int, string> $originalEmails */
            $originalEmails = [];

            foreach ($import->rows as $row) {
                if ($row->action === 'unchanged' && ! $row->target_user_id) {
                    continue;
                }

                if ($row->action === 'create') {
                    $user = User::create([
                        'name' => $row->name,
                        'email' => $row->email,
                        'password' => Hash::make(Str::random(64)),
                        'company_id' => $import->company_id,
                        'company_title' => $import->company?->title,
                        'role' => $row->role,
                        'tariff' => $actor->tariff,
                        'status' => 'pending',
                    ]);
                    $originalEmails[$row->id] = $row->email;
                } else {
                    $user = User::query()->whereKey($row->target_user_id)->lockForUpdate()->firstOrFail();
                    $originalEmails[$row->id] = $user->email;
                    if ($row->action !== 'deactivate') {
                        $user->forceFill([
                            'name' => $row->name,
                            'email' => $row->email,
                            'role' => $row->role,
                            'status' => $row->action === 'reactivate' ? 'pending' : $user->status,
                            'left_at' => $row->action === 'reactivate' ? null : $user->left_at,
                        ])->save();
                    }
                }

                RosterExternalIdentity::updateOrCreate(
                    [
                        'company_id' => $import->company_id,
                        'external_id_normalized' => mb_strtolower($row->external_id),
                    ],
                    [
                        'external_id' => $row->external_id,
                        'user_id' => $user->id,
                    ]
                );
                $resolvedUsers[$row->id] = $user;
            }

            foreach ($import->rows as $row) {
                $user = $resolvedUsers[$row->id] ?? null;
                if (! $user) {
                    continue;
                }

                $supervisor = $this->resolveSupervisorUser(
                    $import->company_id,
                    $row->supervisor_external_id
                );
                $worker = DB::table('company_worker')
                    ->where('company_id', $import->company_id)
                    ->where('email', $originalEmails[$row->id])
                    ->first();
                $status = match ($row->action) {
                    'create', 'reactivate' => 'pending',
                    'deactivate' => 'inactive',
                    default => $user->status ?: 'active',
                };
                $leftAt = $status === 'inactive' ? now() : null;
                $workerValues = [
                    'company_id' => $import->company_id,
                    'name' => $row->name,
                    'email' => $row->email,
                    'role' => $row->role,
                    'department' => $row->department,
                    'supervisor' => $supervisor?->name,
                    'status' => $status,
                    'left_at' => $leftAt,
                    'updated_at' => now(),
                ];

                if ($worker) {
                    DB::table('company_worker')->where('id', $worker->id)->update($workerValues);
                } else {
                    DB::table('company_worker')->insert([
                        ...$workerValues,
                        'created_at' => now(),
                    ]);
                }

                if ($row->action === 'deactivate') {
                    $user->forceFill([
                        'status' => 'inactive',
                        'left_at' => $leftAt,
                        'remember_token' => null,
                    ])->save();
                    $this->organizations->deactivate($user->fresh(), $actor);
                } else {
                    $this->organizations->synchronize(
                        $user->fresh(),
                        $actor,
                        $row->department,
                        $supervisor?->email,
                        $status
                    );
                }

                if (in_array($row->action, ['create', 'reactivate'], true)) {
                    $issued = $this->accountInvitations->issue($user->fresh(), $actor);
                    $invitationIds[] = $issued['invitation']->id;
                }
            }

            $import->update([
                'status' => 'committed',
                'confirmation_token_hash' => null,
                'confirmation_expires_at' => null,
                'committed_at' => now(),
            ]);
            $this->audit->record(
                'roster.import_committed',
                $actor,
                $import->company_id,
                RosterImport::class,
                $import->id,
                $this->countPayload($import)
            );

            return $import->fresh('rows');
        });

        foreach ($invitationIds as $invitationId) {
            try {
                SendAccountInvitation::dispatch($invitationId);
            } catch (\Throwable $exception) {
                Log::error('Account invitation delivery could not be queued after roster commit', [
                    'invitation_id' => $invitationId,
                    'roster_import_id' => $committed->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $committed->fresh('rows');
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(RosterImport $import, bool $includeRows = true): array
    {
        $import->loadMissing('rows');

        return [
            'id' => $import->public_id,
            'status' => $import->status,
            'filename' => $import->original_filename,
            'source_sha256' => $import->source_sha256,
            'counts' => $this->countPayload($import),
            'failure_summary' => $import->failure_summary,
            'confirmation_expires_at' => $import->confirmation_expires_at?->toISOString(),
            'parsed_at' => $import->parsed_at?->toISOString(),
            'committed_at' => $import->committed_at?->toISOString(),
            'rows_purged_at' => $import->rows_purged_at?->toISOString(),
            'rows' => $includeRows
                ? $import->rows->map(fn (RosterImportRow $row): array => [
                    'row_number' => $row->row_number,
                    'external_id' => $row->external_id,
                    'name' => $row->name,
                    'email' => $row->email,
                    'role' => $row->role,
                    'department' => $row->department,
                    'supervisor_external_id' => $row->supervisor_external_id,
                    'status' => $row->desired_status,
                    'action' => $row->action,
                    'changes' => $row->changes ?? [],
                    'errors' => $row->errors ?? [],
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readCsv(string $content): array
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open the roster parser.');
        }
        fwrite($stream, $content);
        rewind($stream);

        $header = fgetcsv($stream, 0, ',', '"', '');
        if ($header === false) {
            fclose($stream);
            throw new \DomainException('The roster CSV has no header row.');
        }
        $header = array_map(
            fn ($value): string => mb_strtolower(trim((string) $value, " \t\n\r\0\x0B\xEF\xBB\xBF")),
            $header
        );
        if (count($header) !== count(array_unique($header))) {
            fclose($stream);
            throw new \DomainException('The roster CSV contains duplicate headers.');
        }

        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $header));
        $unexpected = array_values(array_diff($header, [...self::REQUIRED_HEADERS, ...self::OPTIONAL_HEADERS]));
        if ($missing !== [] || $unexpected !== []) {
            fclose($stream);
            $parts = [];
            if ($missing !== []) {
                $parts[] = 'missing required headers: '.implode(', ', $missing);
            }
            if ($unexpected !== []) {
                $parts[] = 'unexpected headers: '.implode(', ', $unexpected);
            }

            throw new \DomainException('The roster CSV header is invalid ('.implode('; ', $parts).').');
        }

        $rows = [];
        $rowNumber = 1;
        while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
            $rowNumber++;
            if ($this->blankCsvRow($values)) {
                continue;
            }
            if (count($values) !== count($header)) {
                $rows[] = [
                    'row_number' => $rowNumber,
                    '_column_error' => 'Column count does not match the header.',
                ];
            } else {
                $rows[] = [
                    'row_number' => $rowNumber,
                    ...array_combine($header, $values),
                ];
            }
            if (count($rows) > self::MAX_ROWS) {
                fclose($stream);
                throw new \DomainException('Roster CSV files may contain at most '.self::MAX_ROWS.' data rows.');
            }
        }
        fclose($stream);

        if ($rows === []) {
            throw new \DomainException('The roster CSV contains no data rows.');
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  Collection<string, string>  $departments
     * @param  Collection<string, RosterExternalIdentity>  $externalMappings
     * @param  Collection<string, User>  $emailUsers
     * @param  Collection<string, int>  $externalCounts
     * @param  Collection<string, int>  $emailCounts
     * @return array<string, mixed>
     */
    private function normalizeRow(
        array $raw,
        RosterImport $import,
        ?User $actor,
        Collection $departments,
        Collection $externalMappings,
        Collection $emailUsers,
        Collection $externalCounts,
        Collection $emailCounts
    ): array {
        $externalId = trim((string) ($raw['external_id'] ?? ''));
        $name = trim((string) ($raw['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($raw['email'] ?? '')));
        $departmentInput = trim((string) ($raw['department'] ?? ''));
        $department = $departmentInput === ''
            ? null
            : $departments->get(mb_strtolower($departmentInput));
        $supervisorExternalId = trim((string) ($raw['supervisor_external_id'] ?? '')) ?: null;
        $desiredStatus = mb_strtolower(trim((string) ($raw['status'] ?? 'active'))) ?: 'active';
        $role = $this->normalizeRole((string) ($raw['role'] ?? ''));
        $errors = [];

        if (isset($raw['_column_error'])) {
            $errors[] = $raw['_column_error'];
        }
        if ($externalId === '' || mb_strlen($externalId) > 100 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $externalId)) {
            $errors[] = 'external_id is required and may contain only letters, numbers, dot, underscore, colon, and hyphen.';
        }
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            $errors[] = 'name must be between 2 and 255 characters.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors[] = 'email must be a valid address no longer than 255 characters.';
        }
        if (! $role) {
            $errors[] = 'role must be manager, chief, teamlead, employee, or the corresponding number 1-4.';
        } elseif (! $actor || ! $this->users->userCanAssignRole($actor, $role)) {
            $errors[] = 'The signed-in manager may not assign this role.';
        }
        if ($departmentInput !== '' && ! $department) {
            $errors[] = 'department must match an existing department exactly.';
        }
        if (mb_strlen((string) $supervisorExternalId) > 100) {
            $errors[] = 'supervisor_external_id may not exceed 100 characters.';
        }
        if (! in_array($desiredStatus, ['active', 'inactive'], true)) {
            $errors[] = 'status must be active or inactive.';
        }
        if ($externalId !== '' && ($externalCounts[mb_strtolower($externalId)] ?? 0) > 1) {
            $errors[] = 'external_id appears more than once in this file.';
        }
        if ($email !== '' && ($emailCounts[$email] ?? 0) > 1) {
            $errors[] = 'email appears more than once in this file.';
        }

        $mapping = $externalMappings->get(mb_strtolower($externalId));
        $emailUser = $emailUsers->get($email);
        $target = $mapping?->user;
        if ($target && $emailUser && $target->id !== $emailUser->id) {
            $errors[] = 'external_id and email identify different people.';
        } elseif (! $target && $emailUser) {
            if ((int) $emailUser->company_id !== (int) $import->company_id) {
                $errors[] = 'email belongs to an identity in another company and cannot be reassigned.';
            } else {
                $target = $emailUser;
            }
        }
        if ($target && (int) $target->company_id !== (int) $import->company_id) {
            $errors[] = 'external_id resolves outside this company.';
        }
        $targetMapping = $target
            ? $externalMappings->first(
                fn (RosterExternalIdentity $identity): bool => (int) $identity->user_id === (int) $target->id
            )
            : null;
        if ($targetMapping
            && $targetMapping->external_id_normalized !== mb_strtolower($externalId)) {
            $errors[] = 'email is already bound to a different stable external_id in this company.';
        }

        $worker = $target
            ? DB::table('company_worker')
                ->where('company_id', $import->company_id)
                ->where('email', $target->email)
                ->first()
            : null;
        $changes = [];
        $action = 'invalid';
        if ($errors === [] && $role) {
            if ($desiredStatus === 'inactive') {
                if (! $target) {
                    $errors[] = 'An identity must already exist before it can be deactivated.';
                } elseif ($target->status === 'inactive') {
                    $action = 'unchanged';
                } elseif ($actor && $target->id === $actor->id) {
                    $errors[] = 'The signed-in manager cannot deactivate their own account through an import.';
                } elseif ((int) $target->role === Role::MANAGER->value) {
                    $errors[] = 'Manager deactivation requires the governed owner-transfer workflow.';
                } else {
                    $action = 'deactivate';
                    $changes['status'] = ['before' => $target->status, 'after' => 'inactive'];
                }
            } elseif (! $target) {
                $action = 'create';
                $changes = [
                    'name' => ['before' => null, 'after' => $name],
                    'email' => ['before' => null, 'after' => $email],
                    'role' => ['before' => null, 'after' => $role->value],
                    'department' => ['before' => null, 'after' => $department],
                    'supervisor_external_id' => ['before' => null, 'after' => $supervisorExternalId],
                ];
            } else {
                $desired = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $role->value,
                    'department' => $department,
                ];
                $actual = [
                    'name' => $target->name,
                    'email' => mb_strtolower($target->email),
                    'role' => (int) $target->role,
                    'department' => $worker?->department,
                ];
                foreach ($desired as $field => $value) {
                    if ($actual[$field] !== $value) {
                        $changes[$field] = ['before' => $actual[$field], 'after' => $value];
                    }
                }
                if ($supervisorExternalId !== null) {
                    $changes['supervisor_external_id'] = [
                        'before' => null,
                        'after' => $supervisorExternalId,
                    ];
                }

                if ($target->status === 'inactive') {
                    $action = 'reactivate';
                    $changes['status'] = ['before' => 'inactive', 'after' => 'pending'];
                } else {
                    $action = $changes === [] ? 'unchanged' : 'update';
                }
            }
        }

        return [
            'row_number' => (int) ($raw['row_number'] ?? 0),
            'external_id' => $externalId ?: null,
            'name' => $name ?: null,
            'email' => $email ?: null,
            'role' => $role?->value,
            'department' => $department,
            'supervisor_external_id' => $supervisorExternalId,
            'desired_status' => $desiredStatus,
            'action' => $errors === [] ? $action : 'invalid',
            'target_user_id' => $target?->id,
            'target_fingerprint' => $target ? $this->fingerprint($target, $worker) : null,
            'changes' => $changes,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, array<string, mixed>>  $byExternalId
     * @param  Collection<string, RosterExternalIdentity>  $externalMappings
     */
    private function validateSupervisor(
        array &$row,
        Collection $byExternalId,
        Collection $externalMappings,
        int $companyId
    ): void {
        if (! $row['supervisor_external_id']) {
            return;
        }

        $supervisorKey = mb_strtolower($row['supervisor_external_id']);
        if ($supervisorKey === mb_strtolower((string) $row['external_id'])) {
            $row['errors'][] = 'A person cannot supervise themselves.';

            return;
        }

        $stagedSupervisor = $byExternalId->get($supervisorKey);
        if ($stagedSupervisor) {
            if (($stagedSupervisor['errors'] ?? []) !== []
                || $stagedSupervisor['desired_status'] === 'inactive'
                || (int) ($stagedSupervisor['role'] ?? 0) === Role::EMPLOYEE->value) {
                $row['errors'][] = 'supervisor_external_id must reference an active manager, chief, or teamlead.';
            }

            return;
        }

        $mapping = $externalMappings->get($supervisorKey);
        $supervisor = $mapping?->user;
        if (! $supervisor
            || (int) $supervisor->company_id !== $companyId
            || $supervisor->status === 'inactive'
            || (int) $supervisor->role === Role::EMPLOYEE->value) {
            $row['errors'][] = 'supervisor_external_id does not resolve to an active manager, chief, or teamlead in this company.';
        }
    }

    private function assertPreviewIsFresh(RosterImport $import): void
    {
        $stagedByExternalId = $import->rows->keyBy(
            fn (RosterImportRow $row): string => mb_strtolower((string) $row->external_id)
        );

        foreach ($import->rows as $row) {
            $mapping = RosterExternalIdentity::with('user')
                ->where('company_id', $import->company_id)
                ->where('external_id_normalized', mb_strtolower($row->external_id))
                ->first();
            $emailUser = User::whereRaw('LOWER(email) = ?', [mb_strtolower((string) $row->email)])->first();
            $target = $mapping?->user;
            if ($target && $emailUser && (int) $target->id !== (int) $emailUser->id) {
                throw new HttpException(409, "Roster row {$row->row_number} is stale because its external identity and email no longer agree.");
            }
            if (! $target && $emailUser && (int) $emailUser->company_id === (int) $import->company_id) {
                $target = $emailUser;
            }
            if ($emailUser && (int) $emailUser->company_id !== (int) $import->company_id) {
                throw new HttpException(409, "Roster row {$row->row_number} is stale because its email is no longer available.");
            }

            $targetId = $target ? (int) $target->id : 0;
            if ($targetId !== (int) ($row->target_user_id ?? 0)) {
                throw new HttpException(409, "Roster row {$row->row_number} is stale because its identity changed after preview.");
            }
            if ($target) {
                $worker = DB::table('company_worker')
                    ->where('company_id', $import->company_id)
                    ->where('email', $target->email)
                    ->first();
                if (! hash_equals(
                    (string) $row->target_fingerprint,
                    $this->fingerprint($target, $worker)
                )) {
                    throw new HttpException(409, "Roster row {$row->row_number} is stale because roster data changed after preview.");
                }
            }

            if ($row->department && ! DB::table('company_department')
                ->where('company_id', $import->company_id)
                ->where('title', $row->department)
                ->exists()) {
                throw new HttpException(409, "Roster row {$row->row_number} is stale because its department no longer exists.");
            }
            if ($row->supervisor_external_id) {
                $stagedSupervisor = $stagedByExternalId->get(
                    mb_strtolower($row->supervisor_external_id)
                );
                if ($stagedSupervisor
                    && $stagedSupervisor->desired_status !== 'inactive'
                    && (int) $stagedSupervisor->role !== Role::EMPLOYEE->value) {
                    continue;
                }

                $supervisor = $this->resolveSupervisorUser(
                    $import->company_id,
                    $row->supervisor_external_id
                );
                if (! $supervisor
                    || $supervisor->status === 'inactive'
                    || (int) $supervisor->role === Role::EMPLOYEE->value) {
                    throw new HttpException(409, "Roster row {$row->row_number} is stale because its supervisor is unavailable.");
                }
            }
        }
    }

    private function resolveSupervisorUser(int $companyId, ?string $externalId): ?User
    {
        if (! $externalId) {
            return null;
        }

        return RosterExternalIdentity::query()
            ->where('company_id', $companyId)
            ->where('external_id_normalized', mb_strtolower($externalId))
            ->first()
            ?->user()
            ->first();
    }

    private function normalizeRole(string $value): ?Role
    {
        $value = mb_strtolower(trim($value));
        if (ctype_digit($value)) {
            return Role::tryFrom((int) $value);
        }

        return match (str_replace([' ', '-'], '', $value)) {
            'manager', 'companymanager' => Role::MANAGER,
            'chief', 'departmentchief' => Role::CHIEF,
            'teamlead' => Role::TEAMLEAD,
            'employee' => Role::EMPLOYEE,
            default => null,
        };
    }

    private function fingerprint(User $user, ?object $worker): string
    {
        return hash('sha256', json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => mb_strtolower($user->email),
            'role' => (int) $user->role,
            'status' => $user->status,
            'left_at' => $user->left_at?->toISOString(),
            'worker_name' => $worker?->name,
            'worker_email' => $worker ? mb_strtolower((string) $worker->email) : null,
            'worker_role' => $worker ? (int) $worker->role : null,
            'worker_department' => $worker?->department,
            'worker_supervisor' => $worker?->supervisor,
            'worker_status' => $worker?->status,
            'worker_left_at' => $worker?->left_at,
        ], JSON_THROW_ON_ERROR));
    }

    private function assertActorCompany(RosterImport $import, User $actor): void
    {
        if ((int) $actor->company_id !== (int) $import->company_id
            || (int) $actor->role !== Role::MANAGER->value) {
            throw new HttpException(403, 'Only a manager in this company may manage roster imports.');
        }
    }

    /**
     * @return array<string, int>
     */
    private function countPayload(RosterImport $import): array
    {
        return [
            'total' => (int) $import->total_rows,
            'create' => (int) $import->create_count,
            'update' => (int) $import->update_count,
            'reactivate' => (int) $import->reactivate_count,
            'deactivate' => (int) $import->deactivate_count,
            'unchanged' => (int) $import->unchanged_count,
            'errors' => (int) $import->error_count,
        ];
    }

    private function auditPreview(RosterImport $import): void
    {
        $this->audit->record(
            'roster.import_preview_generated',
            $import->creator,
            $import->company_id,
            RosterImport::class,
            $import->id,
            $this->countPayload($import),
            [
                'status' => $import->status,
                'source_sha256' => $import->source_sha256,
            ]
        );
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function blankCsvRow(array $values): bool
    {
        return collect($values)->every(fn ($value): bool => trim((string) $value) === '');
    }
}

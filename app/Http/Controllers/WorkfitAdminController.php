<?php

namespace App\Http\Controllers;

use App\Models\AdvisorWorkItem;
use App\Models\AuditEvent;
use App\Models\Companies;
use App\Models\User;
use App\Services\ActionLoopValueReportService;
use App\Services\AdvisorWorkQueueService;
use App\Services\AuditTrailService;
use App\Services\OnboardingReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkfitAdminController extends Controller
{
    public function __construct(
        protected OnboardingReportService $onboardingReport,
        protected AuditTrailService $audit
    ) {
        $this->middleware(['auth', 'capability:workfit.admin']);
    }

    public function index()
    {
        return view('layouts.admin_modern');
    }

    public function getCompanies()
    {
        $companies = DB::table('companies')
            ->select([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'users.tariff',
            ])
            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
            ->where('users.role', 1) // Assuming manager role links tariff
            ->groupBy([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'users.tariff',
            ])
            ->orderByDesc('companies.id')
            ->paginate(10);

        return response()->json($companies);
    }

    public function getUsers(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function getSubscriptionList()
    {
        $subscriptions = DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->select([
                'subscriptions.*',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->orderByDesc('subscriptions.created_at')
            ->paginate(10);

        return response()->json($subscriptions);
    }

    public function getOnboardingReport(Request $request)
    {
        $report = $this->onboardingReport->report(
            page: max(1, (int) $request->input('page', 1)),
            search: $request->input('search'),
            perPage: 10,
            stage: $request->input('stage'),
        );

        return response()->json($report);
    }

    public function getAuditEvents(Request $request)
    {
        $filters = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'action' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'subject_id' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $companyId = isset($filters['company_id']) ? (int) $filters['company_id'] : null;

        $events = AuditEvent::query()
            ->leftJoin('companies as audit_company', 'audit_company.id', '=', 'audit_events.company_id')
            ->leftJoin('users as audit_actor', 'audit_actor.id', '=', 'audit_events.actor_user_id')
            ->select([
                'audit_events.id',
                'audit_events.stream_key',
                'audit_events.sequence',
                'audit_events.company_id',
                'audit_events.actor_user_id',
                'audit_events.action',
                'audit_events.subject_type',
                'audit_events.subject_id',
                'audit_events.occurred_at',
                'audit_company.title as company_title',
                'audit_actor.name as actor_name',
            ])
            ->when($companyId, fn ($query) => $query->where('audit_events.company_id', $companyId))
            ->when(
                $filters['action'] ?? null,
                fn ($query, string $action) => $query->where('audit_events.action', $action)
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn ($query, string $subjectId) => $query->where('audit_events.subject_id', $subjectId)
            )
            ->orderByDesc('audit_events.occurred_at')
            ->orderByDesc('audit_events.id')
            ->paginate(50);

        $eventData = $events->getCollection()->map(fn (AuditEvent $event): array => [
            'id' => (int) $event->id,
            'stream' => $event->stream_key,
            'sequence' => (int) $event->sequence,
            'company' => $event->company_id ? [
                'id' => (int) $event->company_id,
                'title' => $event->company_title,
            ] : null,
            'actor' => $event->actor_user_id ? [
                'id' => (int) $event->actor_user_id,
                'name' => $event->actor_name ?: 'Deleted user',
            ] : null,
            'action' => $event->action,
            'subject' => $event->subject_type ? [
                'type' => class_basename($event->subject_type),
                'id' => $event->subject_id,
            ] : null,
            'occurred_at' => $event->occurred_at->toISOString(),
        ])->values()->all();

        $integrity = $this->audit->verify($companyId);
        $viewLogged = false;
        if ($companyId !== null || ($integrity['valid'] ?? false)) {
            $this->audit->record(
                'audit.events_viewed',
                $request->user(),
                null,
                $companyId ? Companies::class : null,
                $companyId,
                [],
                [
                    'company_id' => $companyId,
                    'action' => $filters['action'] ?? null,
                    'subject_id' => $filters['subject_id'] ?? null,
                    'page' => (int) $request->input('page', 1),
                ]
            );
            $viewLogged = true;
        }

        return response()->json([
            'events' => [
                'data' => $eventData,
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
            ],
            'integrity' => [
                'valid' => (bool) ($integrity['valid'] ?? false),
                'stream' => $integrity['stream_key'],
                'events' => $integrity['events'] ?? null,
                'failed_event_id' => $integrity['failed_event_id'] ?? null,
            ],
            'view_logged' => $viewLogged,
        ]);
    }

    public function getAdvisorWorkItems(Request $request, AdvisorWorkQueueService $queue)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,claimed,completed,dismissed'],
            'kind' => ['nullable', 'in:activation_risk,finding_review,action_plan_assistance,overdue_followup'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $items = $queue->report(
            $request->user(),
            $filters['status'] ?? null,
            $filters['kind'] ?? null,
            max(1, (int) ($filters['page'] ?? 1))
        );
        $data = $items->getCollection()->map(fn (AdvisorWorkItem $item): array => [
            'id' => $item->id,
            'public_id' => $item->public_id,
            'company' => [
                'id' => $item->company_id,
                'title' => $item->company?->title,
            ],
            'kind' => $item->kind,
            'priority' => $item->priority,
            'status' => $item->status,
            'finding' => $item->finding ? [
                'id' => $item->finding->id,
                'metric_id' => $item->finding->metric_id,
            ] : null,
            'action' => $item->action ? [
                'id' => $item->action->id,
                'title' => $item->action->title,
            ] : null,
            'assignee' => $item->assignee ? [
                'id' => $item->assignee->id,
                'name' => $item->assignee->name,
            ] : null,
            'due_at' => $item->due_at?->toISOString(),
            'created_at' => $item->created_at?->toISOString(),
        ])->values()->all();

        return response()->json([
            'items' => [
                'data' => $data,
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'filters' => [
                'status' => $filters['status'] ?? null,
                'kind' => $filters['kind'] ?? null,
            ],
        ]);
    }

    public function getActionLoopValueReport(
        Request $request,
        ActionLoopValueReportService $reporter
    ) {
        $filters = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);
        $companyId = isset($filters['company_id'])
            ? (int) $filters['company_id']
            : null;
        $report = $reporter->report($companyId);
        $this->audit->record(
            'action.value_report_viewed',
            $request->user(),
            $companyId,
            $companyId ? Companies::class : null,
            $companyId,
            [],
            [
                'schema_version' => $report['schema_version'],
                'scope' => $report['scope']['type'],
            ]
        );

        return response()->json($report);
    }

    public function updateAdvisorWorkItem(
        Request $request,
        AdvisorWorkItem $advisorWorkItem,
        AdvisorWorkQueueService $queue
    ) {
        $data = $request->validate([
            'status' => ['required', 'in:claimed,completed,dismissed'],
        ]);
        try {
            $item = $queue->transition(
                $advisorWorkItem,
                $request->user(),
                $data['status']
            );
        } catch (\DomainException $exception) {
            abort(403, $exception->getMessage());
        }

        return response()->json([
            'status' => 'updated',
            'data' => [
                'id' => $item->id,
                'status' => $item->status,
                'assigned_to_user_id' => $item->assigned_to_user_id,
            ],
        ]);
    }

    public function getCompanyList()
    {
        return $this->getCompanies();
    }

    public function getUsersList()
    {
        return $this->getUsers(request());
    }

    public function getCompany($id)
    {
        $company = Companies::findOrFail($id);

        $manager = User::where('email', $company->manager_email)
            ->where('role', 1)
            ->first();

        $workerCount = DB::table('company_worker')
            ->where('company_id', $id)
            ->count();

        $departmentCount = DB::table('company_department')
            ->where('company_id', $id)
            ->count();

        return response()->json([
            'company' => $company,
            'manager' => $manager,
            'worker_count' => $workerCount,
            'department_count' => $departmentCount,
        ]);
    }
}

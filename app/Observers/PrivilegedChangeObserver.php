<?php

namespace App\Observers;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PrivilegedChangeObserver
{
    public function __construct(protected AuditTrailService $audit) {}

    public function created(Model $model): void
    {
        $this->record($model, 'created', [], $this->safeAttributes($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $after = $this->safeAttributes($model->getChanges());
        if ($after === []) {
            return;
        }

        $before = [];
        foreach (array_keys($after) as $key) {
            $before[$key] = $model->getOriginal($key);
        }

        $this->record($model, 'updated', $before, $after);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $this->safeAttributes($model->getOriginal()), []);
    }

    protected function record(Model $model, string $verb, array $before, array $after): void
    {
        if (! Schema::hasTable('audit_events')) {
            return;
        }

        $actor = auth()->user();
        $companyId = $model->getAttribute('company_id') ?: $actor?->company_id;
        if ($companyId && ! DB::table('companies')->where('id', $companyId)->exists()) {
            $companyId = null;
        }
        $this->audit->record(
            Str::snake(class_basename($model)).".{$verb}",
            $actor,
            $companyId ? (int) $companyId : null,
            $model::class,
            $model->getKey(),
            [
                'before' => $before ?: null,
                'after' => $after ?: null,
            ],
            [
                'actor_type' => $actor ? 'user' : 'system',
            ]
        );
    }

    protected function safeAttributes(array $attributes): array
    {
        return collect($attributes)
            ->except([
                'password',
                'remember_token',
                'token',
                'token_hash',
                'stripe_id',
                'pm_last_four',
                'updated_at',
            ])
            ->all();
    }
}

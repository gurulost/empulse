<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBillingCatalog extends Command
{
    protected $signature = 'billing:sync-catalog';

    protected $description = 'Reconcile the company checkout plan projection with the governed billing catalog.';

    public function handle(): int
    {
        $catalog = collect(config('billing.catalog', []))
            ->filter(fn (array $plan): bool => (bool) ($plan['checkout_enabled'] ?? false));
        $invalid = $catalog->filter(function (array $plan): bool {
            return blank($plan['stripe_price'] ?? null)
                || filter_var($plan['price_cents'] ?? null, FILTER_VALIDATE_INT) === false
                || (int) $plan['price_cents'] <= 0;
        });

        if ($invalid->isNotEmpty()) {
            $this->error(
                'Checkout catalog is incomplete for: '.$invalid->keys()->implode(', ').'.'
            );

            return self::FAILURE;
        }

        DB::transaction(function () use ($catalog): void {
            foreach ($catalog as $slug => $plan) {
                Plan::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $plan['name'],
                        'stripe_plan' => $plan['stripe_price'],
                        'price' => (int) $plan['price_cents'],
                        'description' => $plan['description'],
                    ]
                );
            }

            Plan::whereNotIn('slug', $catalog->keys()->all())->delete();
        });

        $this->info("Billing catalog synchronized ({$catalog->count()} checkout plans).");

        return self::SUCCESS;
    }
}

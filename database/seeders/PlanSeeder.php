<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (config('billing.catalog', []) as $slug => $plan) {
            if (! ($plan['checkout_enabled'] ?? false)
                || blank($plan['stripe_price'] ?? null)
                || ! is_numeric($plan['price_cents'] ?? null)) {
                continue;
            }
            Plan::updateOrCreate(['slug' => $slug], [
                'name' => $plan['name'],
                'stripe_plan' => $plan['stripe_price'],
                'price' => (int) $plan['price_cents'],
                'description' => $plan['description'],
            ]);
        }
    }
}

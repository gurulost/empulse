<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected static function booted(): void
    {
        static::saving(function (self $subscription): void {
            if (blank($subscription->type) && filled($subscription->name)) {
                $subscription->type = $subscription->name;
            }

            if (blank($subscription->name) && filled($subscription->type)) {
                $subscription->name = $subscription->type;
            }
        });
    }
}

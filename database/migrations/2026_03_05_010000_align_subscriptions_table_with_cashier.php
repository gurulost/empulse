<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        if (!Schema::hasColumn('subscriptions', 'type')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('type')->default('default')->after('user_id');
            });
        }

        DB::table('subscriptions')
            ->where(function ($query) {
                $query->whereNull('type')
                    ->orWhere('type', '');
            })
            ->update([
                'type' => DB::raw("COALESCE(name, 'default')"),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscriptions') || !Schema::hasColumn('subscriptions', 'type')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

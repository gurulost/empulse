<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug')
            ->each(function (string $slug): void {
                $keep = DB::table('plans')->where('slug', $slug)->min('id');
                DB::table('plans')
                    ->where('slug', $slug)
                    ->where('id', '!=', $keep)
                    ->delete();
            });

        Schema::table('plans', function (Blueprint $table): void {
            $table->unique('slug', 'plans_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->dropUnique('plans_slug_unique');
        });
    }
};

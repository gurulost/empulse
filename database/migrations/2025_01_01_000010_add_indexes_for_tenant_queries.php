<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('role');
        });

        Schema::table('company_worker', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('role');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['role']);
        });

        Schema::table('company_worker', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['department']);
        });
    }
};


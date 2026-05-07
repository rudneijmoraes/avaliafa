<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Columns broadcast_total_sent and broadcast_total_target were already
        // added by migration 2026_04_15_000001_add_broadcast_sent_at_to_simulados.
        // This migration is kept as a no-op to avoid breaking production deploys
        // where _000001 was applied before this duplicate was detected.
        if (! Schema::hasColumn('simulados', 'broadcast_total_sent')) {
            Schema::table('simulados', function (Blueprint $table) {
                $table->unsignedInteger('broadcast_total_sent')->default(0)->after('broadcast_sent_at');
                $table->unsignedInteger('broadcast_total_target')->default(0)->after('broadcast_total_sent');
            });
        }
    }

    public function down(): void
    {
        // No-op: columns managed by 2026_04_15_000001.
    }
};

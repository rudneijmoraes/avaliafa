<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulados', function (Blueprint $table) {
            $table->timestamp('broadcast_sent_at')->nullable()->after('activation_notified_at');
            $table->unsignedInteger('broadcast_total_sent')->default(0)->after('broadcast_sent_at');
            $table->unsignedInteger('broadcast_total_target')->default(0)->after('broadcast_total_sent');
        });
    }

    public function down(): void
    {
        Schema::table('simulados', function (Blueprint $table) {
            $table->dropColumn(['broadcast_sent_at', 'broadcast_total_sent', 'broadcast_total_target']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulados', function (Blueprint $table) {
            $table->timestamp('activation_notified_at')->nullable()->after('auto_email_enabled');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE simulados
                MODIFY status ENUM('draft','scheduled','active','inactive','archived')
                NOT NULL DEFAULT 'draft'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::table('simulados')
                ->where('status', 'scheduled')
                ->update(['status' => 'draft']);

            DB::statement("
                ALTER TABLE simulados
                MODIFY status ENUM('draft','active','inactive','archived')
                NOT NULL DEFAULT 'draft'
            ");
        }

        Schema::table('simulados', function (Blueprint $table) {
            $table->dropColumn('activation_notified_at');
        });
    }
};

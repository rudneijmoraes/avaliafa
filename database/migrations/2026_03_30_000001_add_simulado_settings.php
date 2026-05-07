<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'simulados', 'key' => 'inscription_banner'],
            ['value' => '', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('settings')->updateOrInsert(
            ['group' => 'simulados', 'key' => 'ranking_enabled'],
            ['value' => 'false', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('settings')->updateOrInsert(
            ['group' => 'simulados', 'key' => 'email_notification_default'],
            ['value' => 'false', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'simulados')
            ->whereIn('key', ['inscription_banner', 'ranking_enabled', 'email_notification_default'])
            ->delete();
    }
};

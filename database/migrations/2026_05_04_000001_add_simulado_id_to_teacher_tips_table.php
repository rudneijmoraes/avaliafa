<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_tips', function (Blueprint $table) {
            $table->foreignId('simulado_id')
                ->nullable()
                ->after('created_by')
                ->constrained('simulados')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_tips', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Simulado::class);
            $table->dropColumn('simulado_id');
        });
    }
};

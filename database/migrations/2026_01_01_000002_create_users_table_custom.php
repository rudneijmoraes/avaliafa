<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('client_system_id')->nullable()->after('id')->constrained('client_systems')->nullOnDelete();
            $table->enum('role', ['super_admin', 'admin', 'coordinator', 'professor', 'student'])->default('student')->after('client_system_id');
            $table->string('cpf', 14)->nullable()->after('email');
            $table->string('external_id')->nullable()->after('cpf');
            $table->string('moodle_user_id')->nullable()->after('external_id');
            $table->boolean('active')->default(true)->after('moodle_user_id');
            $table->softDeletes();
        });

        // Make email unique per client_system
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_system_id']);
            $table->dropColumn(['client_system_id', 'role', 'cpf', 'external_id', 'moodle_user_id', 'active', 'deleted_at']);
            $table->unique('email');
        });
    }
};

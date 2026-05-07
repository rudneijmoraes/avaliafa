<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->json('snapshot');
            $table->unsignedSmallInteger('questions_count')->default(0);
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['exam_id', 'version_number']);
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreignId('exam_version_id')->nullable()->after('exam_id')->constrained('exam_versions')->nullOnDelete();
            $table->string('device_fingerprint', 64)->nullable()->after('user_agent');
            $table->json('device_metadata')->nullable()->after('device_fingerprint');
            $table->unsignedTinyInteger('risk_score')->default(0)->after('device_metadata');
            $table->boolean('is_simulation')->default(false)->after('risk_score');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('owner_department', 100)->nullable()->after('active');
            $table->enum('visibility_scope', ['private', 'department', 'system', 'global'])->default('department')->after('owner_department');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['owner_department', 'visibility_scope']);
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropForeign(['exam_version_id']);
            $table->dropColumn(['exam_version_id', 'device_fingerprint', 'device_metadata', 'risk_score', 'is_simulation']);
        });

        Schema::dropIfExists('exam_versions');
    }
};

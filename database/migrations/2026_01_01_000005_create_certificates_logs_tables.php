<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $table->foreignId('session_id')->constrained('exam_sessions')->restrictOnDelete();
            $table->uuid('code')->unique();
            $table->string('pdf_path')->nullable();
            $table->string('sha256_hash', 64)->nullable();
            $table->decimal('final_score', 5, 2)->unsigned();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('moodle_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->enum('status', ['pending', 'success', 'failed', 'retrying'])->default('pending');
            $table->decimal('grade_sent', 5, 2)->nullable()->unsigned();
            $table->json('moodle_response')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->enum('status', ['pending', 'delivered', 'failed', 'retrying'])->default('pending');
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_system_id')->nullable()->constrained('client_systems')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('moodle_sync_logs');
        Schema::dropIfExists('certificates');
    }
};

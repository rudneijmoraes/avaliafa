<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulado_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->nullable()->constrained('client_systems')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('subject', 180);
            $table->longText('html_body');
            $table->boolean('active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('simulado_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->string('cpf', 14);
            $table->json('metadata')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_access_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_system_id', 'email']);
            $table->unique(['client_system_id', 'cpf']);
        });

        Schema::create('simulados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('simulado_email_templates')->nullOnDelete();
            $table->string('slug', 140)->unique();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->boolean('capture_photo_enabled')->default(false);
            $table->boolean('webcam_enabled')->default(true);
            $table->boolean('fullscreen_enabled')->default(true);
            $table->boolean('show_result_immediately')->default(true);
            $table->boolean('auto_email_enabled')->default(true);
            $table->boolean('moodle_integration_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('exam_id');
            $table->index(['client_system_id', 'status']);
        });

        Schema::create('simulado_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulado_id')->constrained('simulados')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('simulado_participants')->cascadeOnDelete();
            $table->foreignId('exam_session_id')->nullable()->constrained('exam_sessions')->nullOnDelete();
            $table->enum('status', ['registered', 'in_progress', 'completed', 'email_sent', 'cancelled'])->default('registered');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->decimal('raw_score', 7, 2)->nullable();
            $table->decimal('final_score', 7, 2)->nullable();
            $table->unsignedSmallInteger('total_correct')->default(0);
            $table->unsignedSmallInteger('total_wrong')->default(0);
            $table->decimal('percentage_correct', 7, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['simulado_id', 'participant_id']);
            $table->index(['simulado_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulado_registrations');
        Schema::dropIfExists('simulados');
        Schema::dropIfExists('simulado_participants');
        Schema::dropIfExists('simulado_email_templates');
    }
};

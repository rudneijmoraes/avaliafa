<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('token_jti', 64)->unique()->nullable();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'expired', 'terminated', 'graded'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('raw_score', 5, 2)->nullable()->unsigned();
            $table->decimal('final_score', 5, 2)->nullable()->unsigned();
            $table->boolean('passed')->nullable();
            $table->unsignedTinyInteger('violation_count')->default(0);
            $table->json('question_order')->nullable();
            $table->json('choice_order')->nullable();
            $table->boolean('grade_published')->default(false);
            $table->boolean('moodle_synced')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->foreignId('choice_id')->nullable()->constrained('choices')->nullOnDelete();
            $table->json('choice_ids')->nullable();
            $table->text('text_answer')->nullable();
            $table->json('order_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 5, 2)->default(0)->unsigned();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'question_id']);
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->enum('type', [
                'fullscreen_exit', 'fullscreen_denied', 'tab_switch', 'window_blur',
                'shortcut_blocked', 'right_click_blocked', 'violation_warning',
                'violation_limit_reached', 'inactivity_warning', 'inactivity_timeout',
                'webcam_unavailable', 'possible_second_monitor', 'connection_lost',
                'connection_restored',
            ]);
            $table->json('metadata')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });

        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('path');
            $table->string('sha256_hash', 64)->nullable();
            $table->enum('trigger', ['start', 'scheduled', 'violation', 'end']);
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshots');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('exam_sessions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // questions and choices may already exist from a previous run
        if (! Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->enum('type', ['multiple_choice', 'true_false', 'essay', 'multiple_answer', 'ordering'])->default('multiple_choice');
                $table->text('content');
                $table->text('explanation')->nullable();
                $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
                $table->json('tags')->nullable();
                $table->unsignedSmallInteger('version')->default(1);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('choices')) {
            Schema::create('choices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
                $table->text('content');
                $table->boolean('is_correct')->default(false);
                $table->unsignedTinyInteger('order')->default(0);
                $table->timestamps();
            });
        }

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'active', 'closed', 'archived'])->default('draft');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedTinyInteger('max_violations')->default(3);
            $table->boolean('webcam_enabled')->default(false);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_choices')->default(true);
            $table->decimal('passing_score', 5, 2)->default(60.00)->unsigned();
            $table->json('settings')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->decimal('weight', 5, 2)->default(1.00)->unsigned();
            $table->timestamps();

            $table->unique(['exam_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('choices');
        Schema::dropIfExists('questions');
    }
};

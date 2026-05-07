<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('student_id')
                  ->constrained('users');
            $table->enum('type', ['enrollment', 'check']);
            $table->enum('result', [
                'approved',
                'failed',
                'no_face',
                'multiple_faces',
                'skipped',
            ]);
            $table->float('confidence')->nullable();
            $table->string('snapshot_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['exam_session_id', 'created_at']);
            $table->index(['student_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_verifications');
    }
};

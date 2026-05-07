<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->longText('description')->nullable();
            $table->string('video_url', 500);
            $table->enum('video_type', ['youtube', 'vimeo'])->default('youtube');
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['active', 'order']);
        });

        Schema::create('teacher_tip_systems', function (Blueprint $table) {
            $table->foreignId('teacher_tip_id')->constrained('teacher_tips')->cascadeOnDelete();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->primary(['teacher_tip_id', 'client_system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_tip_systems');
        Schema::dropIfExists('teacher_tips');
    }
};

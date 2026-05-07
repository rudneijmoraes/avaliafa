<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->longText('description')->nullable();
            $table->string('file_url', 500);
            $table->string('cover_image', 500)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['active', 'created_at']);
        });

        Schema::create('learning_material_systems', function (Blueprint $table) {
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->primary(['learning_material_id', 'client_system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_systems');
        Schema::dropIfExists('learning_materials');
    }
};

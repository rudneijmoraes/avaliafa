<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('protocol', 40)->unique();
            $table->string('subject', 180);
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('normal');
            $table->timestamps();

            $table->index(['client_system_id', 'status']);
            $table->index(['client_system_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamados');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->nullable()->constrained('client_systems')->nullOnDelete();
            $table->foreignId('simulado_id')->nullable()->constrained('simulados')->nullOnDelete();
            $table->enum('type', ['resultado', 'disponibilidade', 'broadcast'])->index();
            $table->string('recipient_email', 190);
            $table->string('recipient_name', 240)->nullable();
            $table->string('subject', 180)->nullable();
            $table->enum('status', ['sent', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_system_id', 'status', 'created_at'], 'idx_email_logs_system_status_date');
            $table->index(['simulado_id', 'status'], 'idx_email_logs_simulado_status');
            $table->index(['type', 'status'], 'idx_email_logs_type_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

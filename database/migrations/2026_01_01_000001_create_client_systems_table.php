<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('client_id')->unique()->nullable();
            $table->string('client_secret')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('moodle_config')->nullable();
            $table->json('settings')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_systems');
    }
};

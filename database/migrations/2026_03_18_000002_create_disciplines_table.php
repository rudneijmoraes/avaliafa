<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['client_system_id', 'name']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('discipline_id')->nullable()->after('client_system_id')
                ->constrained('disciplines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discipline_id');
        });

        Schema::dropIfExists('disciplines');
    }
};

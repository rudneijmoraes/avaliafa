<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->longText('content')->change();
            $table->longText('explanation')->nullable()->change();
        });

        Schema::table('choices', function (Blueprint $table) {
            $table->longText('content')->change();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('content')->change();
            $table->text('explanation')->nullable()->change();
        });

        Schema::table('choices', function (Blueprint $table) {
            $table->text('content')->change();
        });
    }
};

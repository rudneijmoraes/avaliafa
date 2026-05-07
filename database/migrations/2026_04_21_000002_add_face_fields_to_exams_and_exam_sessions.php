<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('face_reference_photo')->nullable();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('face_recognition_enabled')->default(false)->after('webcam_enabled');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->enum('face_status', [
                'not_required',
                'pending',
                'verified',
                'failed',
            ])->default('not_required')->after('risk_score');

            $table->tinyInteger('face_checks_failed')
                  ->default(0)
                  ->after('face_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('face_reference_photo');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('face_recognition_enabled');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['face_status', 'face_checks_failed']);
        });
    }
};

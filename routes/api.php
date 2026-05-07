<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ExamSessionController;
use App\Http\Controllers\Api\V1\StudentController;
use Illuminate\Support\Facades\Route;

// OAuth2 token endpoint
Route::prefix('v1/auth')->group(function () {
    Route::post('/token', [AuthController::class, 'token'])->name('api.v1.auth.token');
});

// Protected API v1 routes
Route::prefix('v1')->middleware(['auth.api_client', 'api.ip_allowlist'])->group(function () {

    // Students
    Route::post('/students', [StudentController::class, 'upsert'])->name('api.v1.students.upsert');
    Route::post('/students/bulk', [StudentController::class, 'bulkUpsert'])->name('api.v1.students.bulk');
    Route::get('/students/{id}', [StudentController::class, 'show'])->name('api.v1.students.show');

    // Exams
    Route::get('/exams', [ExamController::class, 'index'])->name('api.v1.exams.index');
    Route::get('/exams/{id}', [ExamController::class, 'show'])->name('api.v1.exams.show');
    Route::get('/exams/{id}/results', [ExamController::class, 'results'])->name('api.v1.exams.results');

    // Exam Sessions
    Route::post('/exams/{id}/sessions', [ExamSessionController::class, 'store'])->name('api.v1.sessions.store');
    Route::get('/sessions/{id}/token', [ExamSessionController::class, 'token'])->name('api.v1.sessions.token');
    Route::get('/sessions/{id}/result', [ExamSessionController::class, 'result'])->name('api.v1.sessions.result');
});

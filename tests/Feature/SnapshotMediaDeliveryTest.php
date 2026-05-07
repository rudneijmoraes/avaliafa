<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SnapshotMediaDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_image_is_served_by_application_route(): void
    {
        Storage::fake('public');

        $system = ClientSystem::query()->create([
            'name' => 'Sistema Teste',
            'slug' => 'sistema-teste',
            'client_id' => 'client-media',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'cpf' => '90909090909',
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '80808080808',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova de Midia',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => true,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 7,
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token_jti' => 'snapshot-media-token',
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $path = Storage::disk('public')->putFile("snapshots/{$session->id}", UploadedFile::fake()->image('snapshot.jpg'));

        $snapshot = Snapshot::query()->create([
            'session_id' => $session->id,
            'path' => $path,
            'sha256_hash' => str_repeat('a', 64),
            'trigger' => 'scheduled',
            'captured_at' => now(),
        ]);

        $response = $this->actingAs($professor)->get(route('media.snapshot', $snapshot));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
    }
}

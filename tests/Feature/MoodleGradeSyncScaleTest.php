<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use App\Services\MoodleSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MoodleGradeSyncScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_moodle_rest_sync_normalizes_points_to_ten_point_scale(): void
    {
        Http::fake([
            'https://moodle.example.test/webservice/rest/server.php' => Http::response(['result' => 'ok'], 200),
        ]);

        $system = ClientSystem::query()->create([
            'name' => 'Sistema Moodle Scale',
            'slug' => 'sistema-moodle-scale',
            'active' => true,
            'moodle_config' => [
                'url' => 'https://moodle.example.test',
                'token' => 'rest-token-123',
                'scale' => '0-10',
            ],
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'moodle_user_id' => 'moodle-student-01',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Prova REST Pontuada',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 12,
            'settings' => [
                'moodle' => [
                    'course_id' => 55,
                    'activity_id' => 77,
                ],
            ],
        ]);

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'type' => 'multiple_choice',
            'content' => 'Questao com peso 20',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Alternativa correta',
            'is_correct' => true,
            'order' => 1,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 20]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'question_order' => [$question->id],
            'final_score' => 17.5,
            'raw_score' => 17.5,
            'grade_published' => true,
        ]);

        $result = $this->app->make(MoodleSyncService::class)->sync($session->fresh(['exam.clientSystem', 'student']));

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($student) {
            $data = $request->data();

            return $request->url() === 'https://moodle.example.test/webservice/rest/server.php'
                && ($data['grades[0][studentid]'] ?? null) === $student->moodle_user_id
                && (float) ($data['grades[0][grade]'] ?? 0) === 8.75;
        });
    }

    public function test_moodle_rest_sync_accepts_scalar_success_response_from_core_grades_update_grades(): void
    {
        // Moodle core_grades_update_grades returns 0 for GRADE_UPDATE_OK
        Http::fake([
            'https://moodle.example.test/webservice/rest/server.php' => Http::response(0, 200),
        ]);

        $system = ClientSystem::query()->create([
            'name' => 'Sistema Moodle Escalar',
            'slug' => 'sistema-moodle-escalar',
            'active' => true,
            'moodle_config' => [
                'url' => 'https://moodle.example.test',
                'token' => 'rest-token-123',
                'scale' => '0-10',
            ],
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'moodle_user_id' => 'moodle-student-02',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Prova REST Escalar',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 10,
            'settings' => [
                'moodle' => [
                    'course_id' => 88,
                    'activity_id' => 99,
                ],
            ],
        ]);

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'type' => 'multiple_choice',
            'content' => 'Questao com peso 10',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Alternativa correta',
            'is_correct' => true,
            'order' => 1,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 10]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'question_order' => [$question->id],
            'final_score' => 10.0,
            'raw_score' => 10.0,
            'grade_published' => true,
        ]);

        $result = $this->app->make(MoodleSyncService::class)->sync($session->fresh(['exam.clientSystem', 'student']));

        $this->assertTrue($result);
        $this->assertTrue($session->fresh()->moodle_synced);
        $this->assertDatabaseHas('moodle_sync_logs', [
            'session_id' => $session->id,
            'status' => 'success',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Setting;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use App\Services\SimuladoRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimuladoRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_service_aggregates_completed_simulados_without_question_count_column(): void
    {
        [$system, $professor] = $this->createSystemAndProfessor('Sistema Ranking');
        [$otherSystem, $otherProfessor] = $this->createSystemAndProfessor('Sistema Externo');

        $simuladoA = $this->createSimulado($system, $professor, 'Simulado A');
        $simuladoB = $this->createSimulado($system, $professor, 'Simulado B');
        $simuladoOther = $this->createSimulado($otherSystem, $otherProfessor, 'Simulado Externo');

        $participantTop = SimuladoParticipant::query()->create([
            'client_system_id' => $system->id,
            'first_name' => 'Ana',
            'last_name' => 'Silva',
            'cpf' => '12345678901',
            'email' => 'ana@example.com',
            'phone' => '61999990001',
        ]);

        $participantSecond = SimuladoParticipant::query()->create([
            'client_system_id' => $system->id,
            'first_name' => 'Bruno',
            'last_name' => 'Costa',
            'cpf' => '98765432100',
            'email' => 'bruno@example.com',
            'phone' => '61999990002',
        ]);

        $participantOther = SimuladoParticipant::query()->create([
            'client_system_id' => $otherSystem->id,
            'first_name' => 'Carla',
            'last_name' => 'Souza',
            'cpf' => '11122233344',
            'email' => 'carla@example.com',
            'phone' => '61999990003',
        ]);

        $this->createCompletedRankingRegistration($simuladoA, $participantTop, 1, 8, 2, 95.5);
        $this->createCompletedRankingRegistration($simuladoA, $participantSecond, 1, 6, 4, 40.25);
        $this->createCompletedRankingRegistration($simuladoB, $participantSecond, 2, 6, 4, 39.25);
        $this->createCompletedRankingRegistration($simuladoOther, $participantOther, 1, 10, 0);

        $ranking = app(SimuladoRankingService::class)->getRanking(50, $system->id);
        $rankingByName = collect($ranking)->keyBy('first_name');

        $this->assertCount(2, $ranking);
        $this->assertSame('Ana', $ranking[0]['first_name']);
        $this->assertSame(1, $rankingByName['Ana']['total_simulados']);
        $this->assertSame(8, $rankingByName['Ana']['total_correct']);
        $this->assertSame(10, $rankingByName['Ana']['total_questions']);
        $this->assertSame(80, $rankingByName['Ana']['percentage_correct']);
        $this->assertSame(95.5, $rankingByName['Ana']['score']);
        $this->assertSame('123.***.***-01', $rankingByName['Ana']['cpf']);

        $this->assertSame(2, $rankingByName['Bruno']['total_simulados']);
        $this->assertSame(12, $rankingByName['Bruno']['total_correct']);
        $this->assertSame(20, $rankingByName['Bruno']['total_questions']);
        $this->assertSame(60, $rankingByName['Bruno']['percentage_correct']);
        $this->assertSame(79.5, $rankingByName['Bruno']['score']);
    }

    public function test_ranking_page_loads_for_professor_without_query_exception(): void
    {
        [$system, $professor] = $this->createSystemAndProfessor('Sistema Ranking Web');
        Setting::set('simulados', 'ranking_enabled', 'true');

        $simulado = $this->createSimulado($system, $professor, 'Simulado Página');
        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $system->id,
            'first_name' => 'Daniela',
            'last_name' => 'Lima',
            'cpf' => '33344455566',
            'email' => 'daniela@example.com',
            'phone' => '61999990004',
        ]);

        $this->createCompletedRankingRegistration($simulado, $participant, 1, 7, 3, 7.5);

        $response = $this->actingAs($professor)->get(route('simulados.ranking'));

        $response->assertOk();
        $response->assertSee('Daniela Lima');
        $response->assertSee('7/10');
        $response->assertSee('7.5');
    }

    /**
     * @return array{0: ClientSystem, 1: User}
     */
    private function createSystemAndProfessor(string $name): array
    {
        $slug = strtolower(str_replace(' ', '-', $name));

        $system = ClientSystem::query()->create([
            'name' => $name,
            'slug' => $slug,
            'client_id' => $slug,
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
        ]);

        return [$system, $professor];
    }

    private function createSimulado(ClientSystem $system, User $professor, string $title): Simulado
    {
        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => $title,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [],
        ]);

        return Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => str($title)->slug()->value(),
            'name' => $title,
            'description' => $title.' descrição',
            'status' => 'active',
            'capture_photo_enabled' => false,
            'webcam_enabled' => false,
            'fullscreen_enabled' => false,
            'show_result_immediately' => true,
            'auto_email_enabled' => false,
            'moodle_integration_enabled' => false,
            'settings' => [],
        ]);
    }

    private function createCompletedRankingRegistration(
        Simulado $simulado,
        SimuladoParticipant $participant,
        int $attemptNumber,
        int $totalCorrect,
        int $totalWrong,
        ?float $finalScore = null
    ): void {
        $student = User::factory()->create([
            'client_system_id' => $simulado->client_system_id,
            'role' => 'student',
            'active' => true,
        ]);

        $participant->update(['user_id' => $student->id]);

        $session = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => $attemptNumber,
            'status' => 'graded',
            'is_simulation' => true,
            'raw_score' => $totalCorrect,
            'final_score' => $finalScore ?? $totalCorrect,
            'grade_published' => true,
            'submitted_at' => now()->subMinutes(5),
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'status' => 'completed',
            'registered_at' => now()->subHour(),
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(5),
            'raw_score' => $totalCorrect,
            'final_score' => $finalScore ?? $totalCorrect,
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalWrong,
            'percentage_correct' => ($totalCorrect + $totalWrong) > 0
                ? round(($totalCorrect / ($totalCorrect + $totalWrong)) * 100, 2)
                : 0,
        ]);
    }
}

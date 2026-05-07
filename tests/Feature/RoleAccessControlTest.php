<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_profile_can_access_reports_and_simulados_but_not_exam_crud(): void
    {
        $commercial = $this->createUserWithRole('coordinator', '11122233344');

        $this->actingAs($commercial)->get(route('dashboard'))->assertOk();
        $this->actingAs($commercial)->get(route('relatorios.index'))->assertOk();
        $this->actingAs($commercial)->get(route('simulados.index'))->assertOk();

        $this->actingAs($commercial)->get(route('provas.index'))->assertForbidden();
        $this->actingAs($commercial)->get(route('questoes.index'))->assertForbidden();
        $this->actingAs($commercial)->get(route('estudantes.index'))->assertForbidden();
    }

    public function test_exam_creator_profile_can_manage_exam_area_but_not_people_crud(): void
    {
        $creator = $this->createUserWithRole('professor', '22233344455');

        $this->actingAs($creator)->get(route('provas.index'))->assertOk();
        $this->actingAs($creator)->get(route('questoes.index'))->assertOk();
        $this->actingAs($creator)->get(route('simulados.index'))->assertOk();
        $this->actingAs($creator)->get(route('relatorios.index'))->assertOk();
        $this->actingAs($creator)->get(route('monitor.index'))->assertOk();

        $this->actingAs($creator)->get(route('estudantes.index'))->assertForbidden();
        $this->actingAs($creator)->get(route('configuracoes.index'))->assertForbidden();
    }

    public function test_login_redirect_query_sends_student_to_personal_simulado_area(): void
    {
        $student = $this->createUserWithRole('student', '33344455566', '123456');
        $redirect = route('simulados.minha-area', [], false);

        $this->get(route('login', ['redirect' => $redirect]))->assertOk();

        $response = $this->post(route('login.submit'), [
            'cpf' => '333.444.555-66',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('simulados.minha-area'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_authenticated_non_student_with_participant_link_can_access_personal_simulado_area(): void
    {
        $user = $this->createUserWithRole('professor', '44455566677');

        $exam = Exam::query()->create([
            'client_system_id' => $user->client_system_id,
            'created_by' => $user->id,
            'title' => 'Simulado de Acesso',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $simulado = Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $user->client_system_id,
            'created_by' => $user->id,
            'slug' => 'simulado-acesso-'.$user->id,
            'name' => 'Simulado de Acesso',
            'status' => 'active',
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $user->client_system_id,
            'first_name' => 'Teste',
            'last_name' => 'Professor',
            'email' => 'professor.simulado@example.test',
            'phone' => '11999990000',
            'cpf' => '44455566677',
            'registered_at' => now(),
            'user_id' => $user->id,
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('simulados.minha-area'));

        $response->assertOk();
    }

    private function createUserWithRole(string $role, string $cpf, string $password = 'secret123'): User
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema '.substr($cpf, -3),
            'slug' => 'sistema-'.substr($cpf, -3),
            'active' => true,
        ]);

        return User::factory()->create([
            'client_system_id' => $system->id,
            'role' => $role,
            'cpf' => $cpf,
            'active' => true,
            'password' => Hash::make($password),
        ]);
    }
}

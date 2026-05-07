<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SimuladoTemplateEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_create_page_loads_without_blade_errors(): void
    {
        $professor = $this->createProfessor();

        $this
            ->actingAs($professor)
            ->get(route('simulados.templates.create'))
            ->assertOk();
    }

    public function test_professor_can_send_template_test_email(): void
    {
        Mail::fake();

        $professor = $this->createProfessor();

        $response = $this
            ->actingAs($professor)
            ->postJson(route('simulados.templates.test-email'), [
                'email' => 'destino@faculdadeanasps.com.br',
                'subject' => 'Resultado {{nome_simulado}}',
                'html_body' => '<p>Olá {{primeiro_nome}}, nota {{nota}}</p>',
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);
    }

    private function createProfessor(): User
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Teste',
            'slug' => 'sistema-teste',
            'client_id' => 'client-teste',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        return User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
            'email' => 'professor@faculdadeanasps.com.br',
        ]);
    }
}

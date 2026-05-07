<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRolePromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_promote_student_to_system_admin_from_student_edit_screen(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Perfis',
            'slug' => 'sistema-perfis',
            'active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'first_name' => 'Aluno',
            'last_name' => 'Teste',
            'name' => 'Aluno Teste',
            'cpf' => '12345678901',
            'email' => 'aluno.teste@example.com',
            'active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->put(route('estudantes.update', $student), [
            'first_name' => 'Aluno',
            'last_name' => 'Teste',
            'email' => 'aluno.teste@example.com',
            'client_system_id' => $system->id,
            'active' => '1',
            'profile' => 'administrador',
        ]);

        $response->assertRedirect(route('configuracoes.acessos'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'admin',
            'client_system_id' => $system->id,
            'active' => true,
        ]);
    }
}

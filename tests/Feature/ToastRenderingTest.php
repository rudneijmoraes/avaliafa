<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToastRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_layout_renders_flash_messages_for_toast_root(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Toast',
            'slug' => 'sistema-toast',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['success' => 'Backup criado com sucesso.'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="toast-root"', false);
        $response->assertSee('Backup criado com sucesso.', false);
        $response->assertSee('toast toast-success', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarRankingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_ranking_link_in_sidebar_when_ranking_is_enabled(): void
    {
        Setting::set('simulados', 'ranking_enabled', 'true');

        $system = ClientSystem::query()->create([
            'name' => 'Sistema Ranking Sidebar',
            'slug' => 'sistema-ranking-sidebar',
            'active' => true,
        ]);

        $admin = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('simulados.ranking'), false);
        $response->assertSee('Ranking', false);
    }
}

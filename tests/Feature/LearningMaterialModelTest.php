<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\LearningMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningMaterialModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeSystem(string $slug = 'sys-m'): ClientSystem
    {
        return ClientSystem::create([
            'name' => 'Sistema '.$slug, 'slug' => $slug,
            'client_id' => 'cid-'.$slug, 'client_secret' => 'sec', 'active' => true,
        ]);
    }

    public function test_scope_active_filters_inactive(): void
    {
        LearningMaterial::create(['title' => 'Ativo',   'file_url' => 'https://drive.google.com/a', 'active' => true]);
        LearningMaterial::create(['title' => 'Inativo', 'file_url' => 'https://drive.google.com/b', 'active' => false]);

        $this->assertEquals(1, LearningMaterial::active()->count());
    }

    public function test_scope_for_system_filters_by_client_system(): void
    {
        $sys1 = $this->makeSystem('ms1');
        $sys2 = $this->makeSystem('ms2');

        $m1 = LearningMaterial::create(['title' => 'M1', 'file_url' => 'https://drive.google.com/a', 'active' => true]);
        $m2 = LearningMaterial::create(['title' => 'M2', 'file_url' => 'https://drive.google.com/b', 'active' => true]);

        $m1->systems()->attach($sys1->id);
        $m2->systems()->attach($sys2->id);

        $this->assertEquals(1, LearningMaterial::forSystem($sys1->id)->count());
    }

    public function test_material_ordered_newest_first(): void
    {
        $m1 = LearningMaterial::create(['title' => 'Antigo', 'file_url' => 'https://drive.google.com/a', 'active' => true]);
        $m2 = LearningMaterial::create(['title' => 'Novo',   'file_url' => 'https://drive.google.com/b', 'active' => true]);

        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m1->id)->update(['created_at' => now()->subDays(5)]);
        \Illuminate\Support\Facades\DB::table('learning_materials')->where('id', $m2->id)->update(['created_at' => now()]);

        $first = LearningMaterial::latest()->first();
        $this->assertEquals('Novo', $first->title);
    }

    public function test_cover_url_attribute_returns_null_when_no_cover(): void
    {
        $material = LearningMaterial::create(['title' => 'Sem capa', 'file_url' => 'https://drive.google.com/a', 'active' => true]);
        $this->assertNull($material->cover_url);
    }

    public function test_material_can_belong_to_multiple_systems(): void
    {
        $sys1 = $this->makeSystem('mx1');
        $sys2 = $this->makeSystem('mx2');

        $m = LearningMaterial::create(['title' => 'Multi', 'file_url' => 'https://drive.google.com/x', 'active' => true]);
        $m->systems()->attach([$sys1->id, $sys2->id]);

        $this->assertEquals(2, $m->systems()->count());
    }
}

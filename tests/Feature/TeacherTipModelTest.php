<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\TeacherTip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTipModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeSystem(string $slug = 'sys-a'): ClientSystem
    {
        return ClientSystem::create([
            'name' => 'Sistema '.$slug, 'slug' => $slug,
            'client_id' => 'cid-'.$slug, 'client_secret' => 'sec', 'active' => true,
        ]);
    }

    public function test_detect_video_type_identifies_youtube(): void
    {
        $this->assertEquals('youtube', TeacherTip::detectVideoType('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertEquals('youtube', TeacherTip::detectVideoType('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertEquals('youtube', TeacherTip::detectVideoType('https://www.youtube.com/embed/dQw4w9WgXcQ'));
    }

    public function test_detect_video_type_identifies_vimeo(): void
    {
        $this->assertEquals('vimeo', TeacherTip::detectVideoType('https://vimeo.com/123456789'));
        $this->assertEquals('vimeo', TeacherTip::detectVideoType('https://player.vimeo.com/video/123456789'));
    }

    public function test_embed_url_attribute_generates_correct_youtube_embed(): void
    {
        $tip = TeacherTip::create([
            'title'      => 'Dica 1',
            'video_url'  => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'video_type' => 'youtube',
            'active'     => true,
        ]);

        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $tip->embed_url);
    }

    public function test_embed_url_attribute_generates_correct_youtu_be_embed(): void
    {
        $tip = TeacherTip::create([
            'title'      => 'Dica youtu.be',
            'video_url'  => 'https://youtu.be/dQw4w9WgXcQ',
            'video_type' => 'youtube',
            'active'     => true,
        ]);

        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $tip->embed_url);
    }

    public function test_embed_url_attribute_generates_correct_vimeo_embed(): void
    {
        $tip = TeacherTip::create([
            'title'      => 'Dica Vimeo',
            'video_url'  => 'https://vimeo.com/123456789',
            'video_type' => 'vimeo',
            'active'     => true,
        ]);

        $this->assertEquals('https://player.vimeo.com/video/123456789', $tip->embed_url);
    }

    public function test_scope_active_filters_inactive(): void
    {
        TeacherTip::create(['title' => 'Ativa',   'video_url' => 'https://youtu.be/aaa', 'video_type' => 'youtube', 'active' => true]);
        TeacherTip::create(['title' => 'Inativa', 'video_url' => 'https://youtu.be/bbb', 'video_type' => 'youtube', 'active' => false]);

        $this->assertEquals(1, TeacherTip::active()->count());
    }

    public function test_scope_for_system_filters_by_client_system(): void
    {
        $sys1 = $this->makeSystem('s1');
        $sys2 = $this->makeSystem('s2');

        $tip1 = TeacherTip::create(['title' => 'T1', 'video_url' => 'https://youtu.be/aaa', 'video_type' => 'youtube', 'active' => true]);
        $tip2 = TeacherTip::create(['title' => 'T2', 'video_url' => 'https://youtu.be/bbb', 'video_type' => 'youtube', 'active' => true]);

        $tip1->systems()->attach($sys1->id);
        $tip2->systems()->attach($sys2->id);

        $this->assertEquals(1, TeacherTip::forSystem($sys1->id)->count());
        $this->assertEquals('T1', TeacherTip::forSystem($sys1->id)->first()->title);
    }

    public function test_tip_can_belong_to_multiple_systems(): void
    {
        $sys1 = $this->makeSystem('m1');
        $sys2 = $this->makeSystem('m2');

        $tip = TeacherTip::create(['title' => 'Multi', 'video_url' => 'https://youtu.be/aaa', 'video_type' => 'youtube', 'active' => true]);
        $tip->systems()->attach([$sys1->id, $sys2->id]);

        $this->assertEquals(2, $tip->systems()->count());
        $this->assertEquals(1, TeacherTip::forSystem($sys1->id)->count());
        $this->assertEquals(1, TeacherTip::forSystem($sys2->id)->count());
    }
}

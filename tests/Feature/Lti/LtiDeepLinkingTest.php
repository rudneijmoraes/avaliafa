<?php

namespace Tests\Feature\Lti;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LtiDeepLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_deep_linking_persists_resource_link_mapping_for_exam(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Graduacao',
            'slug' => 'graduacao',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'email' => 'teacher@example.test',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'AV2 2026/2',
            'status' => 'draft',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $registration = LtiRegistration::query()->create([
            'client_system_id' => $system->id,
            'issuer' => 'https://moodle.example.test',
            'client_id' => 'avaliafa-tool-client',
            'deployment_id' => 'deployment-01',
            'platform_name' => 'Moodle',
            'active' => true,
        ]);

        $response = $this->actingAs($teacher)->post(route('lti.deep-linking'), [
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-link-01',
            'context_id' => 'course-9001',
            'context_label' => 'TST-9001',
            'context_title' => 'Turma de Teste',
            'lineitem_url' => 'https://moodle.example.test/lineitems/42',
            'lineitems_url' => 'https://moodle.example.test/lineitems',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('lti_resource_links', [
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-link-01',
            'context_id' => 'course-9001',
            'lineitem_url' => 'https://moodle.example.test/lineitems/42',
        ]);

        $resourceLink = LtiResourceLink::query()->first();

        $this->assertNotNull($resourceLink);
        $this->assertSame($exam->id, $resourceLink->exam_id);
    }
}

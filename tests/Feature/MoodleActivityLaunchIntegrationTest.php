<?php

namespace Tests\Feature;

use Tests\TestCase;

class MoodleActivityLaunchIntegrationTest extends TestCase
{
    public function test_moodle_activity_resolver_falls_back_to_exam_title_for_activity_name(): void
    {
        $contents = file_get_contents(base_path('app/Services/MoodleActivityResolver.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('?: $exam->title', $contents);
    }

    public function test_exam_routes_expose_moodle_launch_entrypoints(): void
    {
        $contents = file_get_contents(base_path('routes/web.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("Route::get('/moodle/{exam}', [ExamWebController::class, 'moodleLaunch'])->name('moodle-launch');", $contents);
        $this->assertStringContainsString("Route::post('/moodle/{exam}', [ExamWebController::class, 'startFromMoodle'])->name('moodle-launch.start');", $contents);
    }

    public function test_exam_web_controller_handles_launch_from_moodle(): void
    {
        $contents = file_get_contents(base_path('app/Http/Controllers/Web/ExamWebController.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('public function moodleLaunch(Request $request, Exam $exam)', $contents);
        $this->assertStringContainsString('public function startFromMoodle(Request $request, Exam $exam)', $contents);
        $this->assertStringContainsString("return redirect()->route('exam.start', ['token' => \$token]);", $contents);
    }

    public function test_exam_detail_view_exposes_moodle_launch_url(): void
    {
        $contents = file_get_contents(base_path('resources/views/provas/show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("route('exam.moodle-launch', \$exam)", $contents);
        $this->assertStringContainsString('URL única para atividade no Moodle', $contents);
        $this->assertStringContainsString('Cole esta URL na atividade do Moodle para abrir a prova no AvaliaFA.', $contents);
    }

    public function test_moodle_launch_view_exists_with_student_lookup_form(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/moodle-launch.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('Entrar na avaliação', $contents);
        $this->assertStringContainsString('name="student_reference"', $contents);
        $this->assertStringContainsString('Informe seu CPF para localizar sua sessão liberada.', $contents);
    }
}

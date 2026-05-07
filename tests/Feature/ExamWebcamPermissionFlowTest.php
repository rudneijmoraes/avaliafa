<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamWebcamPermissionFlowTest extends TestCase
{
    public function test_exam_show_gate_keeps_retry_action_available_until_ready(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('x-show="!ready"', $contents);
        $this->assertStringContainsString('this.webcamError = false;', $contents);
    }

    public function test_exam_start_gate_offers_manual_webcam_recheck(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/start.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('Verificar camera novamente', $contents);
        $this->assertStringContainsString('@click="runChecks()"', $contents);
        $this->assertStringContainsString('this.webcamError = false;', $contents);
    }

    public function test_exam_start_layout_keeps_primary_action_visible_in_dedicated_box(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/start.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('align-items: flex-start;', $contents);
        $this->assertStringContainsString('overflow-y: auto; overflow-x: hidden;', $contents);
        $this->assertStringContainsString('.action-box {', $contents);
        $this->assertStringContainsString('position: sticky;', $contents);
        $this->assertStringContainsString('class="action-box"', $contents);
    }

    public function test_exam_flows_include_obstructed_webcam_detection(): void
    {
        $startContents = file_get_contents(base_path('resources/views/exam/start.blade.php'));
        $showContents = file_get_contents(base_path('resources/views/exam/show.blade.php'));
        $engineContents = file_get_contents(base_path('resources/js/secure-exam-engine.js'));
        $riskContents = file_get_contents(base_path('app/Services/RiskScoreService.php'));

        $this->assertNotFalse($startContents);
        $this->assertNotFalse($showContents);
        $this->assertNotFalse($engineContents);
        $this->assertNotFalse($riskContents);

        $this->assertStringContainsString('Camera obstruida', $startContents);
        $this->assertStringContainsString('Camera obstruida', $showContents);
        $this->assertStringContainsString('webcam_obstructed', $engineContents);
        $this->assertStringContainsString('webcam_obstructed', $riskContents);
    }

    public function test_exam_flows_capture_snapshot_on_violation_and_before_finish(): void
    {
        $showContents = file_get_contents(base_path('resources/views/exam/show.blade.php'));
        $engineContents = file_get_contents(base_path('resources/js/secure-exam-engine.js'));

        $this->assertNotFalse($showContents);
        $this->assertNotFalse($engineContents);
        $this->assertStringContainsString("captureSnapshot('violation')", $engineContents);
        $this->assertStringContainsString("captureSnapshot('end')", $showContents);
    }
}

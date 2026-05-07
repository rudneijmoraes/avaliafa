<?php

namespace Tests\Feature;

use Tests\TestCase;

class MonitorAuditTrailTest extends TestCase
{
    public function test_monitor_controller_exposes_snapshot_and_security_data(): void
    {
        $contents = file_get_contents(base_path('app/Http/Controllers/Web/ExamWebController.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("withCount('snapshots')", $contents);
        $this->assertStringContainsString("'snapshots' =>", $contents);
        $this->assertStringContainsString("'security_events' =>", $contents);
        $this->assertStringContainsString("fileUrl()", $contents);
        $this->assertStringContainsString("limit(12)", $contents);
    }

    public function test_monitor_view_renders_audit_panel_with_snapshots(): void
    {
        $contents = file_get_contents(base_path('resources/views/monitor/index.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('Auditoria da sessao', $contents);
        $this->assertStringContainsString('Capturas de auditoria', $contents);
        $this->assertStringContainsString('selectedSession?.snapshots', $contents);
        $this->assertStringContainsString('Abrir auditoria da sessao', $contents);
        $this->assertStringContainsString('Trilha de seguranca', $contents);
    }
}

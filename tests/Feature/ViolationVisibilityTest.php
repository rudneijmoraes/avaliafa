<?php

namespace Tests\Feature;

use Tests\TestCase;

class ViolationVisibilityTest extends TestCase
{
    public function test_student_facing_exam_views_do_not_expose_exact_violation_limit(): void
    {
        $startContents = file_get_contents(base_path('resources/views/exam/start.blade.php'));
        $engineContents = file_get_contents(base_path('resources/js/secure-exam-engine.js'));

        $this->assertNotFalse($startContents);
        $this->assertNotFalse($engineContents);
        $this->assertStringNotContainsString('advertências</strong> antes do encerramento automático', $startContents);
        $this->assertStringContainsString('poderá encerrar automaticamente', $startContents);
        $this->assertStringNotContainsString('Aviso ${violationCount} de ${config.maxViolations}', $engineContents);
        $this->assertStringNotContainsString('Restam ${remaining}', $engineContents);
        $this->assertStringContainsString('Advertência de segurança', $engineContents);
    }
}

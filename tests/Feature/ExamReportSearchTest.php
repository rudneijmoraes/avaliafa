<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamReportSearchTest extends TestCase
{
    public function test_report_controller_search_covers_split_student_names_and_simulation_filter(): void
    {
        $contents = file_get_contents(base_path('app/Http/Controllers/Web/RelatorioController.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("\$includeSimulations = \$this->resolveIncludeSimulations(\$request, \$exam);", $contents);
        $this->assertStringContainsString("\$tokens = collect(preg_split('/\\s+/', \$search) ?: [])", $contents);
        $this->assertStringContainsString("->orWhere('first_name', 'like', '%'.\$token.'%')", $contents);
        $this->assertStringContainsString("->orWhere('last_name', 'like', '%'.\$token.'%')", $contents);
        $this->assertStringContainsString("->orWhere('cpf', 'like', '%'.\$normalizedCpf.'%')", $contents);
        $this->assertStringContainsString("if (\$request->has('incluir_simulados')) {", $contents);
        $this->assertStringContainsString('private function examHasOnlySimulationSessions(Exam $exam): bool', $contents);
        $this->assertStringContainsString("if (! \$includeSimulations) {", $contents);
    }

    public function test_report_view_exposes_simulation_filter_and_student_name_fallback(): void
    {
        $contents = file_get_contents(base_path('resources/views/relatorios/prova.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('Incluir simulados', $contents);
        $this->assertStringContainsString('<input type="hidden" name="incluir_simulados" value="0">', $contents);
        $this->assertStringContainsString("{{ \$includeSimulations ? 'checked' : '' }}", $contents);
        $this->assertStringContainsString('sessoes oficiais por padrao', $contents);
        $this->assertStringContainsString('$studentName = trim((string)', $contents);
        $this->assertStringContainsString('badge badge-warning', $contents);
        $this->assertStringContainsString('Simulado', $contents);
    }
}

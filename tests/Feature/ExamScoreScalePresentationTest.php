<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamScoreScalePresentationTest extends TestCase
{
    public function test_exam_list_and_form_use_points_instead_of_fixed_zero_to_ten_scale(): void
    {
        $indexContents = file_get_contents(base_path('resources/views/provas/index.blade.php'));
        $formContents = file_get_contents(base_path('resources/views/provas/form.blade.php'));
        $startContents = file_get_contents(base_path('resources/views/exam/start.blade.php'));

        $this->assertNotFalse($indexContents);
        $this->assertNotFalse($formContents);
        $this->assertNotFalse($startContents);

        $this->assertStringContainsString("number_format((float) \$exam->passing_score, 2, ',', '.')", $indexContents);
        $this->assertStringNotContainsString('$exam->passing_score }}%', $indexContents);
        $this->assertStringContainsString('Pontos Mín.', $indexContents);
        $this->assertStringContainsString('Pontuação mínima', $formContents);
        $this->assertStringNotContainsString('max="10"', $formContents);
        $this->assertStringContainsString("\$exam?->passing_score ?? 6", $formContents);
        $this->assertStringNotContainsString('Escala decimal de 0 a 10.', $formContents);
        $this->assertStringNotContainsString('<span class="stat-unit">%</span>', $startContents);
    }
}

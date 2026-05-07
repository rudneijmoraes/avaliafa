<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamGradingChoiceIdNormalizationTest extends TestCase
{
    public function test_grading_normalizes_choice_ids_before_strict_comparison(): void
    {
        $contents = file_get_contents(base_path('app/Http/Controllers/Web/ExamWebController.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('$answeredChoiceId = filled($answer->choice_id) ? (int) $answer->choice_id : null;', $contents);
        $this->assertStringContainsString('$expectedChoiceId = filled($correctChoiceId) ? (int) $correctChoiceId : null;', $contents);
        $this->assertStringContainsString('$answeredChoiceId === $expectedChoiceId', $contents);
    }

    public function test_answer_model_casts_choice_id_to_integer(): void
    {
        $contents = file_get_contents(base_path('app/Models/Answer.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("'choice_id' => 'integer'", $contents);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamQuestionNavigationContrastTest extends TestCase
{
    public function test_current_question_button_does_not_mix_with_answer_state_classes(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("'current':    currentIndex ===", $contents);
        $this->assertStringContainsString("'answered':   currentIndex !==", $contents);
        $this->assertStringContainsString("'unanswered': currentIndex !==", $contents);
    }
}


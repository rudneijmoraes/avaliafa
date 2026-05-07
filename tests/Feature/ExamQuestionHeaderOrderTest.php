<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamQuestionHeaderOrderTest extends TestCase
{
    public function test_block_question_header_uses_same_index_as_sidebar_navigation(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('title="Questão {{ $i + 1 }}"', $contents);
        $this->assertStringContainsString('Questão {{ $i + 1 }} de {{ count($questions) }}', $contents);
        $this->assertStringNotContainsString('$displayQuestionNumber', $contents);
        $this->assertStringNotContainsString('Questão {{ $displayNumber }} de {{ count($questions) }}', $contents);
    }
}

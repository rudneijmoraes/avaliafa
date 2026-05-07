<?php

namespace Tests\Feature;

use Tests\TestCase;

class TypographyConfigurationTest extends TestCase
{
    public function test_primary_layouts_and_canonical_docs_reference_inter_as_main_font(): void
    {
        $files = [
            base_path('resources/views/layouts/app.blade.php'),
            base_path('resources/views/layouts/exam.blade.php'),
            base_path('resources/views/monitor/index.blade.php'),
            base_path('resources/views/auth/login.blade.php'),
            base_path('resources/views/exam/start.blade.php'),
            base_path('resources/views/exam/result.blade.php'),
            base_path('resources/views/exam/error.blade.php'),
            base_path('docs/docs/FONTE_UNICA_CONTEXTO_ATUAL.md'),
            base_path('docs/skills/SKILL_design-system.md'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertNotFalse($contents, "Failed to read file [{$file}].");
            $this->assertStringContainsString('Inter', $contents, "File [{$file}] should reference Inter.");
            $this->assertStringNotContainsString('Plus Jakarta Sans', $contents, "File [{$file}] should not reference Plus Jakarta Sans.");
        }
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamFormSecuritySettingsTest extends TestCase
{
    public function test_exam_form_uses_native_checkboxes_for_security_settings(): void
    {
        $contents = file_get_contents(base_path('resources/views/provas/form.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("['name' => 'webcam_enabled'", $contents);
        $this->assertStringContainsString("['name' => 'shuffle_questions'", $contents);
        $this->assertStringContainsString("['name' => 'shuffle_choices'", $contents);
        $this->assertStringContainsString('name="snapshot_interval_seconds"', $contents);
        $this->assertStringContainsString('type="checkbox"', $contents);
        $this->assertStringContainsString("{{ (bool) \$toggle['value'] ? 'checked' : '' }}", $contents);
        $this->assertStringNotContainsString('type="hidden" name="{{ $toggle[\'name\'] }}"', $contents);
        $this->assertStringNotContainsString('x-data="{ enabled:', $contents);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPageHorizontalLayoutTest extends TestCase
{
    public function test_login_view_uses_horizontal_shell_layout(): void
    {
        $contents = file_get_contents(base_path('resources/views/auth/login.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.login-shell', $contents);
        $this->assertStringContainsString('.login-layout', $contents);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.1fr) minmax(400px, 480px);', $contents);
        $this->assertStringContainsString("asset('imagem/logo-deitada-transparente.png')", $contents);
        $this->assertStringContainsString('alt="Logo da Faculdade Anasps"', $contents);
        $this->assertStringContainsString('<p class="brand-eyebrow">Faculdade Anasps</p>', $contents);
        $this->assertStringContainsString('<h1 class="brand-title">AvaliaFA</h1>', $contents);
    }

    public function test_exam_start_view_uses_horizontal_shell_layout(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/start.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.start-shell', $contents);
        $this->assertStringContainsString('.start-layout', $contents);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.38fr) minmax(320px, 0.82fr);', $contents);
        $this->assertStringContainsString('width: min(1180px, 100%);', $contents);
    }

    public function test_exam_error_view_uses_horizontal_shell_layout(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/error.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.error-shell', $contents);
        $this->assertStringContainsString('.error-layout', $contents);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);', $contents);
    }

    public function test_lti_map_view_uses_horizontal_shell_layout(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/lti-map.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.map-shell', $contents);
        $this->assertStringContainsString('.map-layout', $contents);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.05fr) minmax(340px, 0.95fr);', $contents);
    }

    public function test_lti_map_success_view_uses_horizontal_shell_layout(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/lti-map-success.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.success-shell', $contents);
        $this->assertStringContainsString('.success-layout', $contents);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.02fr) minmax(300px, 0.9fr);', $contents);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class ToastRenderingFallbackTest extends TestCase
{
    public function test_layout_keeps_server_toasts_visible_and_uses_resilient_bootstrap(): void
    {
        $contents = file_get_contents(base_path('resources/views/layouts/app.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('<div class="toast toast-{{ $toast[\'type\'] }}" data-toast-id="{{ $index + 1 }}">', $contents);
        $this->assertStringNotContainsString('style="opacity:0;transform:translateX(24px) scale(0.96)"', $contents);
        $this->assertStringContainsString('if (document.readyState === \'loading\') {', $contents);
        $this->assertStringContainsString('__initAppToasts();', $contents);
    }
}

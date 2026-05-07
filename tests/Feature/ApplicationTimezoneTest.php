<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_uses_brasilia_timezone_by_default(): void
    {
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
        $this->assertSame('America/Sao_Paulo', date_default_timezone_get());
    }

    public function test_general_settings_timezone_overrides_default_timezone(): void
    {
        Setting::set('geral', 'timezone', 'America/Manaus');

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('America/Manaus', config('app.timezone'));
        $this->assertSame('America/Manaus', date_default_timezone_get());
    }
}

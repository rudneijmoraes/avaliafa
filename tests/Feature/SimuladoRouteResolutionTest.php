<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class SimuladoRouteResolutionTest extends TestCase
{
    public function test_templates_path_resolves_to_templates_route(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/simulados/templates', 'GET'));

        $this->assertSame('simulados.templates.index', $route->getName());
    }

    public function test_numeric_simulado_path_resolves_to_show_route(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/simulados/123', 'GET'));

        $this->assertSame('simulados.show', $route->getName());
    }

    public function test_participant_pdf_path_resolves_to_participant_pdf_route(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/simulados/123/participantes/456/pdf', 'GET'));

        $this->assertSame('simulados.participants.export.pdf', $route->getName());
    }
}

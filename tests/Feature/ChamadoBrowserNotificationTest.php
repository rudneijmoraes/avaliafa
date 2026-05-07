<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ChamadoBrowserNotificationTest extends TestCase
{
    public function test_chamado_routes_are_not_registered(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('chamados.index'));
        $this->assertNull(Route::getRoutes()->getByName('chamados.store'));
        $this->assertNull(Route::getRoutes()->getByName('chamados.notifications'));
        $this->assertNull(Route::getRoutes()->getByName('chamados.status.update'));
    }
}

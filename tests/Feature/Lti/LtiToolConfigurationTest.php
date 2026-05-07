<?php

namespace Tests\Feature\Lti;

use Tests\TestCase;

class LtiToolConfigurationTest extends TestCase
{
    public function test_lti_models_and_session_columns_are_declared(): void
    {
        $this->assertFileExists(app_path('Models/LtiRegistration.php'));
        $this->assertFileExists(app_path('Models/LtiResourceLink.php'));
        $this->assertFileExists(app_path('Models/LtiLaunchLog.php'));

        $session = file_get_contents(app_path('Models/ExamSession.php'));

        $this->assertNotFalse($session);
        $this->assertStringContainsString("'launch_source'", $session);
        $this->assertStringContainsString("'lti_registration_id'", $session);
        $this->assertStringContainsString("'lti_resource_link_id'", $session);
    }

    public function test_system_form_exposes_lti_configuration_fields(): void
    {
        $contents = file_get_contents(resource_path('views/sistemas/form.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('LTI 1.3', $contents);
        $this->assertStringContainsString('name="lti_issuer"', $contents);
        $this->assertStringContainsString('name="lti_client_id"', $contents);
    }

    public function test_web_routes_expose_lti_tool_entrypoints(): void
    {
        $contents = file_get_contents(base_path('routes/web.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("Route::match(['GET', 'POST'], '/lti/login', [LtiToolController::class, 'login'])->name('lti.login');", $contents);
        $this->assertStringContainsString("Route::match(['GET', 'POST'], '/lti/launch', [LtiToolController::class, 'launch'])->name('lti.launch');", $contents);
        $this->assertStringContainsString("Route::get('/lti/jwks', [LtiToolController::class, 'jwks'])->name('lti.jwks');", $contents);
        $this->assertStringContainsString("Route::post('/lti/deep-linking', [LtiToolController::class, 'deepLinking'])->name('lti.deep-linking');", $contents);
    }

    public function test_lti_controller_exposes_lti_entrypoints_backed_by_services(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/Web/LtiToolController.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('public function login(Request $request)', $contents);
        $this->assertStringContainsString('public function launch(Request $request)', $contents);
        $this->assertStringContainsString('$this->oidcService->jwks()', $contents);
        $this->assertStringContainsString('$this->oidcService->initiateLogin($request)', $contents);
        $this->assertStringContainsString('$this->launchService->launch($request->all(), $launchContext)', $contents);
        $this->assertStringContainsString('$this->deepLinkingService->mapResourceLink($user, $validated)', $contents);
    }

    public function test_lti_placeholder_controller_declares_skeleton_services(): void
    {
        $this->assertFileExists(app_path('Services/Lti/LtiOidcService.php'));
        $this->assertFileExists(app_path('Services/Lti/LtiLaunchService.php'));
        $this->assertFileExists(app_path('Services/Lti/LtiDeepLinkingService.php'));

        $contents = file_get_contents(app_path('Http/Controllers/Web/LtiToolController.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('LtiOidcService', $contents);
        $this->assertStringContainsString('LtiLaunchService', $contents);
        $this->assertStringContainsString('LtiDeepLinkingService', $contents);
    }
}

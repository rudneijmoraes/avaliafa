<?php

namespace Tests\Feature\Lti;

use App\Models\ClientSystem;
use App\Models\LtiRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Mockery;
use Tests\TestCase;

class LtiSystemConfigurationSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sistemas_store_syncs_lti_registration_from_form_configuration(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'active' => true,
        ]);

        $passportClient = new Client();
        $passportClient->forceFill(['id' => 'generated-client-id']);
        $passportClient->plainSecret = 'generated-secret';

        $mock = Mockery::mock(ClientRepository::class);
        $mock->shouldReceive('createClientCredentialsGrantClient')
            ->once()
            ->andReturn($passportClient);

        $this->app->instance(ClientRepository::class, $mock);

        $response = $this->actingAs($admin)->post(route('sistemas.store'), [
            'name' => 'Graduacao',
            'slug' => 'graduacao',
            'active' => '1',
            'lti_enabled' => '1',
            'lti_issuer' => 'https://moodle.example.test',
            'lti_client_id' => 'avaliafa-tool-client',
            'lti_deployment_id' => 'deployment-01',
            'lti_platform_login_url' => 'https://moodle.example.test/mod/lti/auth.php',
            'lti_platform_token_url' => 'https://moodle.example.test/mod/lti/token.php',
            'lti_platform_keyset_url' => 'https://moodle.example.test/mod/lti/certs.php',
        ]);

        $response->assertRedirect(route('sistemas.index'));

        $system = ClientSystem::query()->where('slug', 'graduacao')->first();

        $this->assertNotNull($system);
        $this->assertSame('generated-client-id', $system->client_id);

        $registration = LtiRegistration::query()->where('client_system_id', $system->id)->first();

        $this->assertNotNull($registration);
        $this->assertSame('https://moodle.example.test', $registration->issuer);
        $this->assertSame('avaliafa-tool-client', $registration->client_id);
        $this->assertSame('deployment-01', $registration->deployment_id);
        $this->assertSame('https://moodle.example.test/mod/lti/auth.php', $registration->auth_login_url);
        $this->assertSame('https://moodle.example.test/mod/lti/token.php', $registration->auth_token_url);
        $this->assertSame('https://moodle.example.test/mod/lti/certs.php', $registration->keyset_url);
        $this->assertTrue($registration->active);
    }
}

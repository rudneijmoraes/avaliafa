<?php

namespace Database\Seeders;

use App\Models\ClientSystem;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class ClientSystemSeeder extends Seeder
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
    ) {}

    public function run(): void
    {
        $systems = [
            [
                'name' => 'Graduação',
                'slug' => 'graduacao',
                'webhook_url' => null,
                'moodle_config' => [
                    'url' => env('MOODLE_GRADUACAO_URL', 'https://moodle.anasps.edu.br'),
                    'certifier_url' => null,
                    'token' => env('MOODLE_GRADUACAO_TOKEN', ''),
                    'activity_id' => 0,
                    'course_id' => 0,
                    'scale' => '0-10',
                ],
                'settings' => [
                    'max_attempts' => 3,
                    'show_results_after' => 'submission',
                ],
            ],
            [
                'name' => 'Pós-Graduação',
                'slug' => 'pos',
                'webhook_url' => null,
                'moodle_config' => [
                    'url' => env('MOODLE_POS_URL', 'https://moodle.anasps.edu.br'),
                    'certifier_url' => null,
                    'token' => env('MOODLE_POS_TOKEN', ''),
                    'activity_id' => 0,
                    'course_id' => 0,
                    'scale' => '0-10',
                ],
                'settings' => [
                    'max_attempts' => 2,
                    'show_results_after' => 'graded',
                ],
            ],
            [
                'name' => 'Certificadora',
                'slug' => 'certificadora',
                'webhook_url' => null,
                'moodle_config' => [
                    'url' => null,
                    'certifier_url' => env('MOODLE_CERTIFICADORA_URL', 'https://certificadora.moodle.exemplo.br'),
                    'token' => env('MOODLE_CERTIFICADORA_TOKEN', ''),
                    'activity_id' => 0,
                    'course_id' => 0,
                    'scale' => '0-10',
                ],
                'settings' => [
                    'max_attempts' => 1,
                    'show_results_after' => 'graded',
                    'certificate_auto_issue' => true,
                ],
            ],
        ];

        foreach ($systems as $data) {
            $clientSystem = ClientSystem::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['active' => true])
            );

            $oauthClient = $clientSystem->client_id
                ? $this->clientRepository->findActive($clientSystem->client_id)
                : null;

            if (! $oauthClient || ! $oauthClient->hasGrantType('client_credentials') || empty($clientSystem->client_secret)) {
                $oauthClient = $this->clientRepository->createClientCredentialsGrantClient($clientSystem->name);

                $clientSystem->forceFill([
                    'client_id' => (string) $oauthClient->getKey(),
                    'client_secret' => $oauthClient->plainSecret,
                ])->save();
            }
        }

        $this->command->info('Client systems seeded: Graduação, Pós-Graduação, Certificadora');
    }
}

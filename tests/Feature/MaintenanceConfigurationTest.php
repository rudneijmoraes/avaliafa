<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\SystemMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MaintenanceConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_page_shows_update_section_and_current_version(): void
    {
        Setting::setGroup('maintenance', [
            'current_version' => '2026.03.18',
            'last_update_at' => '2026-03-18 10:15:00',
        ]);

        $response = $this
            ->actingAs($this->createSuperAdmin())
            ->get(route('configuracoes.backup'));

        $response->assertOk();
        $response->assertSee('Atualiza');
        $response->assertSee('Vers');
        $response->assertSee('2026.03.18');
        $response->assertSee('Arquivo ZIP');
    }

    public function test_super_admin_can_apply_update_zip_and_register_new_version(): void
    {
        $targetPath = storage_path('framework/testing/maintenance-target');
        $backupPath = storage_path('framework/testing/maintenance-backups');
        $updatePath = storage_path('framework/testing/maintenance-updates');

        File::deleteDirectory($targetPath);
        File::deleteDirectory($backupPath);
        File::deleteDirectory($updatePath);
        File::ensureDirectoryExists($targetPath);
        File::ensureDirectoryExists($backupPath);
        File::ensureDirectoryExists($updatePath);

        File::ensureDirectoryExists($targetPath.'/resources/views');
        File::put($targetPath.'/resources/views/version.txt', 'versao antiga');
        File::put($targetPath.'/artisan', '<?php echo "artisan";');
        File::put($targetPath.'/composer.json', '{"name":"avalia-fa/test"}');

        config([
            'maintenance.project_source_path' => $targetPath,
            'maintenance.update_target_path' => $targetPath,
            'maintenance.backup_storage_path' => $backupPath,
            'maintenance.update_storage_path' => $updatePath,
        ]);

        $package = $this->createUpdatePackage([
            'resources/views/version.txt' => 'versao nova aplicada',
            'docs/release-note.txt' => 'release 2026.03.19',
        ]);

        $response = $this
            ->actingAs($this->createSuperAdmin())
            ->post(route('configuracoes.backup.atualizar'), [
                'version' => '2026.03.19',
                'package' => $package,
                'notes' => 'Pacote acumulado de manutencao',
            ]);

        $response->assertRedirect(route('configuracoes.backup'));
        $response->assertSessionHas('success');

        $this->assertSame('versao nova aplicada', File::get($targetPath.'/resources/views/version.txt'));
        $this->assertSame('2026.03.19', Setting::get('maintenance', 'current_version'));
        $this->assertSame('Pacote acumulado de manutencao', Setting::get('maintenance', 'last_update_notes'));
        $this->assertNotEmpty(Setting::get('maintenance', 'last_update_package'));
        $this->assertNotEmpty(Setting::get('maintenance', 'last_update_backup'));
        $this->assertSame('success', Setting::get('maintenance', 'last_update_migration_status'));

        $history = json_decode((string) Setting::get('maintenance', 'update_history', '[]'), true);

        $this->assertIsArray($history);
        $this->assertSame('2026.03.19', $history[0]['version'] ?? null);
        $this->assertSame('success', $history[0]['migration_status'] ?? null);
        $this->assertNotEmpty(glob($backupPath.'/*.zip'));
    }

    public function test_update_zip_runs_force_migrations_after_copying_files(): void
    {
        $targetPath = storage_path('framework/testing/maintenance-target');
        $backupPath = storage_path('framework/testing/maintenance-backups');
        $updatePath = storage_path('framework/testing/maintenance-updates');

        File::deleteDirectory($targetPath);
        File::deleteDirectory($backupPath);
        File::deleteDirectory($updatePath);
        File::ensureDirectoryExists($targetPath);
        File::ensureDirectoryExists($backupPath);
        File::ensureDirectoryExists($updatePath);

        File::ensureDirectoryExists($targetPath.'/resources/views');
        File::put($targetPath.'/resources/views/version.txt', 'versao antiga');
        File::put($targetPath.'/artisan', '<?php echo "artisan";');
        File::put($targetPath.'/composer.json', '{"name":"avalia-fa/test"}');

        config([
            'maintenance.project_source_path' => $targetPath,
            'maintenance.update_target_path' => $targetPath,
            'maintenance.backup_storage_path' => $backupPath,
            'maintenance.update_storage_path' => $updatePath,
        ]);

        $package = $this->createUpdatePackage([
            'resources/views/version.txt' => 'versao nova aplicada',
        ]);

        Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once()->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Nothing to migrate.');
        Artisan::shouldReceive('call')->with('cache:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('config:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('route:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('view:clear')->once()->andReturn(0);

        app(SystemMaintenanceService::class)->applyUpdate($package, '2026.03.20');

        $this->assertSame('success', Setting::get('maintenance', 'last_update_migration_status'));
    }

    public function test_can_apply_already_stored_package_to_local_target(): void
    {
        $targetPath = storage_path('framework/testing/maintenance-target');
        $backupPath = storage_path('framework/testing/maintenance-backups');
        $updatePath = storage_path('framework/testing/maintenance-updates');

        File::deleteDirectory($targetPath);
        File::deleteDirectory($backupPath);
        File::deleteDirectory($updatePath);
        File::ensureDirectoryExists($targetPath);
        File::ensureDirectoryExists($backupPath);
        File::ensureDirectoryExists($updatePath.'/packages');

        File::ensureDirectoryExists($targetPath.'/resources/views');
        File::put($targetPath.'/resources/views/version.txt', 'versao antiga');
        File::put($targetPath.'/artisan', '<?php echo "artisan";');
        File::put($targetPath.'/composer.json', '{"name":"avalia-fa/test"}');

        config([
            'maintenance.project_source_path' => $targetPath,
            'maintenance.update_target_path' => $targetPath,
            'maintenance.backup_storage_path' => $backupPath,
            'maintenance.update_storage_path' => $updatePath,
        ]);

        $this->createStoredUpdatePackage(
            $updatePath.'/packages',
            [
                'resources/views/version.txt' => 'versao vinda de pacote salvo',
            ],
            'pacote-local.zip'
        );

        Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once()->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Nothing to migrate.');
        Artisan::shouldReceive('call')->with('cache:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('config:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('route:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('view:clear')->once()->andReturn(0);

        app(SystemMaintenanceService::class)->applyStoredPackage('pacote-local.zip', '2026.03.21');

        $this->assertSame('versao vinda de pacote salvo', File::get($targetPath.'/resources/views/version.txt'));
        $this->assertSame('2026.03.21', Setting::get('maintenance', 'current_version'));
        $this->assertSame('pacote-local.zip', Setting::get('maintenance', 'last_update_package'));
    }

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'active' => true,
            'email' => 'super-admin@example.test',
        ]);
    }

    private function createUpdatePackage(array $files): UploadedFile
    {
        $directory = storage_path('framework/testing/update-package-'.uniqid());
        File::ensureDirectoryExists($directory);

        $zipPath = $directory.'/avaliafa-update.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();

        return new UploadedFile($zipPath, 'avaliafa-update.zip', 'application/zip', null, true);
    }

    private function createStoredUpdatePackage(string $directory, array $files, string $filename): string
    {
        File::ensureDirectoryExists($directory);

        $zipPath = $directory.'/'.$filename;
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $path => $contents) {
            $zip->addFromString($path, $contents);
        }

        $zip->close();

        return $zipPath;
    }
}

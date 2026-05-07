<?php

namespace App\Console\Commands;

use App\Services\SystemMaintenanceService;
use Illuminate\Console\Command;

class ApplyStoredUpdatePackageCommand extends Command
{
    protected $signature = 'maintenance:apply-package
        {version : Versao que sera registrada no historico}
        {package? : Nome do ZIP em storage/app/updates/packages}
        {--notes= : Observacoes da atualizacao}
        {--latest : Aplica automaticamente o ZIP mais recente valido}';

    protected $description = 'Aplica no ambiente local um pacote ZIP ja salvo em storage/app/updates/packages';

    public function __construct(
        private readonly SystemMaintenanceService $maintenanceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $selectedPackage = $this->resolvePackageSelection();

        if ($selectedPackage === null) {
            return self::FAILURE;
        }

        try {
            $result = $this->maintenanceService->applyStoredPackage(
                $selectedPackage,
                (string) $this->argument('version'),
                $this->option('notes') ? (string) $this->option('notes') : null,
            );
        } catch (\Throwable $e) {
            $this->error('Falha ao aplicar pacote: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Atualizacao {$result['version']} aplicada com sucesso.");
        $this->line("Pacote: {$result['package']}");
        $this->line("Backup preventivo: {$result['backup']}");
        $this->line("Status migrations: {$result['migration_status']}");

        return self::SUCCESS;
    }

    private function resolvePackageSelection(): ?string
    {
        $packagesDir = config('maintenance.update_storage_path', storage_path('app/updates'))
            .DIRECTORY_SEPARATOR.'packages';

        if (! is_dir($packagesDir)) {
            $this->error('Diretorio de pacotes nao encontrado: '.$packagesDir);

            return null;
        }

        $requestedPackage = $this->argument('package');
        $useLatest = (bool) $this->option('latest') || ! is_string($requestedPackage) || trim($requestedPackage) === '';

        if (! $useLatest) {
            return basename((string) $requestedPackage);
        }

        $packages = glob($packagesDir.DIRECTORY_SEPARATOR.'*.zip') ?: [];

        if ($packages === []) {
            $this->error('Nenhum pacote ZIP foi encontrado em storage/app/updates/packages.');

            return null;
        }

        usort($packages, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        foreach ($packages as $packagePath) {
            $size = filesize($packagePath);

            if ($size !== false && $size > 0) {
                $packageName = basename($packagePath);
                $this->line("Pacote selecionado: {$packageName}");

                return $packageName;
            }
        }

        $this->error('Todos os pacotes ZIP encontrados estao vazios.');

        return null;
    }
}

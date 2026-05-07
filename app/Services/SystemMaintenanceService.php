<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemMaintenanceService
{
    public function summary(): array
    {
        $settings = Setting::getGroup('maintenance');
        $history = json_decode((string) ($settings['update_history'] ?? '[]'), true);

        return [
            'current_version' => $settings['current_version'] ?? 'Nao definida',
            'last_update_at' => $settings['last_update_at'] ?? null,
            'last_update_package' => $settings['last_update_package'] ?? null,
            'last_update_notes' => $settings['last_update_notes'] ?? null,
            'last_update_backup' => $settings['last_update_backup'] ?? null,
            'last_update_migration_status' => $settings['last_update_migration_status'] ?? null,
            'last_update_migration_output' => $settings['last_update_migration_output'] ?? null,
            'history' => is_array($history) ? $history : [],
        ];
    }

    public function createBackupArchive(?string $label = null): array
    {
        $timestamp = now()->format('Y-m-d_His');
        $suffix = $label ? '_'.Str::slug($label, '_') : '';
        $zipName = "backup_avaliafa_{$timestamp}{$suffix}.zip";
        $backupDir = $this->backupStoragePath();

        File::ensureDirectoryExists($backupDir);

        $zipPath = $backupDir.DIRECTORY_SEPARATOR.$zipName;
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Nao foi possivel criar o arquivo ZIP de backup.');
        }

        $sqlDump = $this->generateDatabaseDump();
        if ($sqlDump !== null && $sqlDump !== '') {
            $zip->addFromString("database/avaliafa_{$timestamp}.sql", $sqlDump);
        }

        foreach (['app', 'config', 'database', 'docs', 'public', 'resources', 'routes', 'storage'] as $folder) {
            $folderPath = $this->projectSourcePath().DIRECTORY_SEPARATOR.$folder;

            if (is_dir($folderPath)) {
                $this->addFolderToZip($zip, $folderPath, $folder);
            }
        }

        foreach (['.env', '.env.example', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'artisan'] as $file) {
            $filePath = $this->projectSourcePath().DIRECTORY_SEPARATOR.$file;

            if (is_file($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }

        $zip->close();

        return [
            'name' => $zipName,
            'path' => $zipPath,
            'size' => $this->formatBytes((int) filesize($zipPath)),
        ];
    }

    public function listBackups(): array
    {
        $path = $this->backupStoragePath();

        if (! is_dir($path)) {
            return [];
        }

        $files = glob($path.DIRECTORY_SEPARATOR.'*.zip') ?: [];
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'name' => basename($file),
                'size' => $this->formatBytes((int) filesize($file)),
                'date' => date('d/m/Y H:i', filemtime($file)),
            ];
        }

        usort($result, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $result;
    }

    public function applyUpdate(UploadedFile $package, string $version, ?string $notes = null): array
    {
        $version = $this->normalizeVersion($version);
        $updatesRoot = $this->updateStoragePath();
        $packagesDir = $updatesRoot.DIRECTORY_SEPARATOR.'packages';

        File::ensureDirectoryExists($packagesDir);

        $packageFilename = now()->format('Ymd_His').'_'.$this->sanitizeFilename($package->getClientOriginalName() ?: 'update.zip');
        $storedPackagePath = $package->move($packagesDir, $packageFilename)->getPathname();

        return $this->applyStoredPackagePath($storedPackagePath, $packageFilename, $version, $notes);
    }

    public function applyStoredPackage(string $packageFilename, string $version, ?string $notes = null): array
    {
        $version = $this->normalizeVersion($version);
        $selectedPackage = basename(trim($packageFilename));

        if ($selectedPackage === '') {
            throw new \RuntimeException('Informe um nome de pacote valido.');
        }

        $storedPackagePath = $this->updateStoragePath()
            .DIRECTORY_SEPARATOR.'packages'
            .DIRECTORY_SEPARATOR.$selectedPackage;

        if (! is_file($storedPackagePath)) {
            throw new \RuntimeException('O pacote informado nao foi encontrado em storage/app/updates/packages.');
        }

        return $this->applyStoredPackagePath($storedPackagePath, $selectedPackage, $version, $notes);
    }

    private function applyStoredPackagePath(string $storedPackagePath, string $packageFilename, string $version, ?string $notes): array
    {
        $this->assertPackageReadable($storedPackagePath);

        $updatesRoot = $this->updateStoragePath();
        $extractDir = $updatesRoot.DIRECTORY_SEPARATOR.'extract_'.Str::random(16);
        File::ensureDirectoryExists($extractDir);

        $zip = new \ZipArchive();

        if ($zip->open($storedPackagePath) !== true) {
            @unlink($storedPackagePath);
            throw new \RuntimeException('O pacote ZIP enviado nao pode ser aberto. Verifique se o arquivo nao esta corrompido.');
        }

        $zipIsOpen = true;

        try {
            $zip->extractTo($extractDir);

            $packageRoot = $this->resolvePackageRoot($extractDir);
            $preUpdateBackup = $this->createBackupArchive('pre_update_'.$version);
            $copiedPaths = $this->copyAllowedPackagePaths($packageRoot, $this->updateTargetPath());

            if ($copiedPaths === 0) {
                throw new \RuntimeException('O pacote ZIP nao contem arquivos permitidos para atualizacao.');
            }

            $migration = $this->runMigrations();

            $history = $this->summary()['history'];
            array_unshift($history, [
                'version' => $version,
                'package' => $packageFilename,
                'notes' => $notes,
                'applied_at' => now()->toDateTimeString(),
                'migration_status' => $migration['status'],
            ]);

            $history = array_slice($history, 0, 10);

            Setting::setGroup('maintenance', [
                'current_version' => $version,
                'last_update_at' => now()->toDateTimeString(),
                'last_update_package' => $packageFilename,
                'last_update_notes' => $notes ?? '',
                'last_update_backup' => $preUpdateBackup['name'],
                'last_update_migration_status' => $migration['status'],
                'last_update_migration_output' => $migration['output'],
                'update_history' => json_encode($history, JSON_UNESCAPED_UNICODE),
            ]);

            $this->clearCaches();

            return [
                'version' => $version,
                'package' => $packageFilename,
                'backup' => $preUpdateBackup['name'],
                'migration_status' => $migration['status'],
            ];
        } finally {
            if ($zipIsOpen) {
                $zip->close();
            }

            File::deleteDirectory($extractDir);
        }
    }

    public function clearCaches(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Reset OPcache so updated PHP files take effect immediately
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    private function runMigrations(): array
    {
        $exitCode = Artisan::call('migrate', ['--force' => true]);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                $output !== '' ? $output : 'A atualizacao foi aplicada, mas as migrations falharam.'
            );
        }

        return [
            'status' => 'success',
            'output' => $output,
        ];
    }

    private function generateDatabaseDump(): ?string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'mysql' => $this->generateMySqlDump(),
            'sqlite' => $this->generateSqliteDump(),
            default => null,
        };
    }

    private function generateMySqlDump(): ?string
    {
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $mysqldumpPaths = [
            'mysqldump',
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($mysqldumpPaths as $bin) {
            $command = sprintf(
                '%s --host=%s --port=%s --user=%s %s --routines --triggers --single-transaction %s',
                escapeshellarg($bin),
                escapeshellarg((string) $dbHost),
                escapeshellarg((string) $dbPort),
                escapeshellarg((string) $dbUser),
                $dbPass ? '--password='.escapeshellarg((string) $dbPass) : '',
                escapeshellarg((string) $dbName)
            );

            $output = [];
            $returnCode = -1;
            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode === 0 && $output !== []) {
                return implode("\n", $output);
            }
        }

        return $this->generateMySqlDumpViaPdo((string) $dbName);
    }

    private function generateMySqlDumpViaPdo(string $dbName): ?string
    {
        try {
            $pdo = DB::connection()->getPdo();
            $lines = [
                '-- AvaliaFA Database Backup',
                '-- Gerado em: '.date('Y-m-d H:i:s'),
                '-- Metodo: PDO fallback',
                "-- Banco: {$dbName}",
                '',
                'SET FOREIGN_KEY_CHECKS=0;',
                '',
            ];

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
                $lines[] = ($create['Create Table'] ?? '').';';
                $lines[] = '';
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                $this->appendInsertStatements($lines, $pdo, (string) $table, $rows);
            }

            $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

            return implode("\n", $lines);
        } catch (\Throwable) {
            return null;
        }
    }

    private function generateSqliteDump(): ?string
    {
        try {
            $pdo = DB::connection()->getPdo();
            $lines = [
                '-- AvaliaFA SQLite Backup',
                '-- Gerado em: '.date('Y-m-d H:i:s'),
                'PRAGMA foreign_keys=OFF;',
                'BEGIN TRANSACTION;',
                '',
            ];

            $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($tables as $table) {
                $name = (string) ($table['name'] ?? '');
                $sql = (string) ($table['sql'] ?? '');

                if ($name === '' || $sql === '') {
                    continue;
                }

                $lines[] = "DROP TABLE IF EXISTS \"{$name}\";";
                $lines[] = $sql.';';
                $lines[] = '';

                $rows = $pdo->query("SELECT * FROM \"{$name}\"")->fetchAll(\PDO::FETCH_ASSOC);
                $this->appendInsertStatements($lines, $pdo, $name, $rows, '"');
            }

            $lines[] = 'COMMIT;';
            $lines[] = 'PRAGMA foreign_keys=ON;';

            return implode("\n", $lines);
        } catch (\Throwable) {
            return null;
        }
    }

    private function appendInsertStatements(array &$lines, \PDO $pdo, string $table, array $rows, string $quoteChar = '`'): void
    {
        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        $colList = $quoteChar.implode($quoteChar.', '.$quoteChar, $columns).$quoteChar;

        foreach (array_chunk($rows, 500) as $chunk) {
            $values = [];

            foreach ($chunk as $row) {
                $vals = array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    array_values($row)
                );
                $values[] = '('.implode(', ', $vals).')';
            }

            $tableName = $quoteChar.$table.$quoteChar;
            $lines[] = "INSERT INTO {$tableName} ({$colList}) VALUES";
            $lines[] = implode(",\n", $values).';';
            $lines[] = '';
        }
    }

    private function addFolderToZip(\ZipArchive $zip, string $folderPath, string $zipPrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS),
                function ($current) {
                    if (! $current instanceof \SplFileInfo) {
                        return true;
                    }

                    $relativePath = str_replace('\\', '/', $current->getPathname());

                    foreach (['/vendor', '/node_modules', '/.git', '/storage/framework', '/storage/logs', '/storage/app/backups', '/storage/app/updates'] as $needle) {
                        if (str_contains($relativePath, $needle)) {
                            return false;
                        }
                    }

                    return true;
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = $zipPrefix.'/'.substr($file->getPathname(), strlen($folderPath) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
                continue;
            }

            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    private function copyAllowedPackagePaths(string $packageRoot, string $targetRoot): int
    {
        $copied = 0;

        foreach ((array) config('maintenance.allowed_update_paths', []) as $relativePath) {
            $source = $packageRoot.DIRECTORY_SEPARATOR.$relativePath;

            if (! file_exists($source)) {
                continue;
            }

            $destination = $targetRoot.DIRECTORY_SEPARATOR.$relativePath;
            $this->copyPath($source, $destination);
            $copied++;
        }

        return $copied;
    }

    private function copyPath(string $source, string $destination): void
    {
        if (is_dir($source)) {
            File::ensureDirectoryExists($destination);

            $items = File::allFiles($source);
            foreach ($items as $item) {
                $relative = str_replace('\\', '/', $item->getRelativePathname());
                $targetFile = $destination.DIRECTORY_SEPARATOR.$relative;
                File::ensureDirectoryExists(dirname($targetFile));
                File::copy($item->getPathname(), $targetFile);
            }

            return;
        }

        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);
    }

    private function resolvePackageRoot(string $extractDir): string
    {
        $items = array_values(array_filter(scandir($extractDir) ?: [], fn ($item) => ! in_array($item, ['.', '..'], true)));

        if (count($items) !== 1) {
            return $extractDir;
        }

        $firstItem = (string) $items[0];
        $allowedRoots = array_map('strval', (array) config('maintenance.allowed_update_paths', []));

        if (in_array($firstItem, $allowedRoots, true)) {
            return $extractDir;
        }

        $candidate = $extractDir.DIRECTORY_SEPARATOR.$firstItem;

        return is_dir($candidate) ? $candidate : $extractDir;
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);

        return trim((string) $sanitized, '_');
    }

    private function normalizeVersion(string $version): string
    {
        $normalized = trim($version);

        if ($normalized === '') {
            throw new \RuntimeException('A nova versao e obrigatoria para aplicar a atualizacao.');
        }

        return $normalized;
    }

    private function assertPackageReadable(string $storedPackagePath): void
    {
        if (! is_file($storedPackagePath)) {
            throw new \RuntimeException('O pacote ZIP informado nao foi encontrado.');
        }

        clearstatcache(true, $storedPackagePath);
        $size = filesize($storedPackagePath);

        if ($size === false || $size <= 0) {
            @unlink($storedPackagePath);
            throw new \RuntimeException('O pacote ZIP enviado esta vazio ou invalido. Envie um arquivo ZIP valido.');
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function projectSourcePath(): string
    {
        return (string) config('maintenance.project_source_path', base_path());
    }

    private function updateTargetPath(): string
    {
        return (string) config('maintenance.update_target_path', base_path());
    }

    private function backupStoragePath(): string
    {
        return (string) config('maintenance.backup_storage_path', storage_path('app/backups'));
    }

    private function updateStoragePath(): string
    {
        return (string) config('maintenance.update_storage_path', storage_path('app/updates'));
    }
}

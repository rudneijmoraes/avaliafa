<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\Setting;
use App\Models\User;
use App\Services\MoodleSyncService;
use App\Services\SystemMaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ConfiguracaoController extends Controller
{
    public function __construct(
        private readonly SystemMaintenanceService $maintenanceService,
    ) {}

    private function ensureSuperAdmin(): void
    {
        abort_if(! Auth::user()->isSuperAdmin(), 403);
    }

    /**
     * Tela principal de configurações com os cards.
     */
    public function index()
    {
        $this->ensureSuperAdmin();

        return view('configuracoes.index');
    }

    // ── Geral ─────────────────────────────────────────────────────────

    public function geral()
    {
        $this->ensureSuperAdmin();

        $settings = Setting::getGroup('geral');
        $settings['simulado_inscription_banner'] = Setting::get('simulados', 'inscription_banner', '');
        $settings['simulado_ranking_enabled'] = Setting::get('simulados', 'ranking_enabled', 'false');
        $settings['simulado_email_notification_default'] = Setting::get('simulados', 'email_notification_default', 'false');
        $settings['simulado_banner_image_url'] = Setting::get('simulados', 'banner_image_url', '');

        return view('configuracoes.geral', compact('settings'));
    }

    public function salvarGeral(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'app_name'        => 'required|string|max:100',
            'institution_name' => 'nullable|string|max:255',
            'support_email'   => 'nullable|email|max:255',
            'support_phone'   => 'nullable|string|max:30',
            'timezone'        => 'required|string|max:50',
            'locale'          => 'required|string|in:pt_BR,en',
            'simulado_inscription_banner' => 'nullable|string|max:5000',
            'simulado_banner_image_url' => 'nullable|string|max:2048',
            'simulado_ranking_enabled' => 'nullable|string|max:10',
            'simulado_email_notification_default' => 'nullable|string|max:10',
        ]);

        Setting::setGroup('geral', [
            'app_name' => $data['app_name'],
            'institution_name' => $data['institution_name'] ?? '',
            'support_email' => $data['support_email'] ?? '',
            'support_phone' => $data['support_phone'] ?? '',
            'timezone' => $data['timezone'],
            'locale' => $data['locale'],
        ]);

        Setting::set('simulados', 'inscription_banner', $this->sanitizeBannerHtml($data['simulado_inscription_banner'] ?? ''));
        Setting::set('simulados', 'banner_image_url', trim((string) ($data['simulado_banner_image_url'] ?? '')));
        Setting::set('simulados', 'ranking_enabled', $request->boolean('simulado_ranking_enabled') ? 'true' : 'false');
        Setting::set('simulados', 'email_notification_default', $request->boolean('simulado_email_notification_default') ? 'true' : 'false');

        return back()->with('success', 'Configurações gerais salvas com sucesso.');
    }

    private function sanitizeBannerHtml(string $html): string
    {
        $clean = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html) ?? '';
        $clean = preg_replace('#javascript:#i', '', $clean) ?? '';
        $clean = preg_replace('#on\w+\s*=\s*["\'].*?["\']#i', '', $clean) ?? '';

        return strip_tags($clean, '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><div><span>');
    }

    // ── E-mail / SMTP ────────────────────────────────────────────────

    public function email()
    {
        $this->ensureSuperAdmin();

        $settings = Setting::getGroup('email');

        return view('configuracoes.email', compact('settings'));
    }

    public function salvarEmail(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'smtp_host'       => 'required|string|max:255',
            'smtp_port'       => 'required|integer|min:1|max:65535',
            'smtp_username'   => 'nullable|string|max:255',
            'smtp_password'   => 'nullable|string|max:255',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'required|string|max:255',
        ]);

        Setting::setGroup('email', $data);

        return back()->with('success', 'Configurações de e-mail salvas com sucesso.');
    }

    public function testarEmail(Request $request)
    {
        $this->ensureSuperAdmin();

        $request->validate(['test_email' => 'required|email']);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'Este é um e-mail de teste do AvaliaFA.',
                function ($msg) use ($request) {
                    $msg->to($request->test_email)
                        ->subject('Teste de e-mail — AvaliaFA');
                }
            );

            return back()->with('success', 'E-mail de teste enviado para ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('error', 'Falha ao enviar e-mail: ' . $e->getMessage());
        }
    }

    // ── Segurança ────────────────────────────────────────────────────

    public function seguranca()
    {
        $this->ensureSuperAdmin();

        $settings = Setting::getGroup('seguranca');

        return view('configuracoes.seguranca', compact('settings'));
    }

    public function salvarSeguranca(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'max_login_attempts'   => 'required|integer|min:1|max:20',
            'lockout_duration'     => 'required|integer|min:1|max:120',
            'session_lifetime'     => 'required|integer|min:5|max:480',
            'password_min_length'  => 'required|integer|min:6|max:32',
            'require_uppercase'    => 'nullable|boolean',
            'require_numbers'      => 'nullable|boolean',
            'require_symbols'      => 'nullable|boolean',
            'exam_max_violations'  => 'required|integer|min:1|max:50',
            'exam_webcam_required' => 'nullable|boolean',
        ]);

        $data['require_uppercase']    = $request->boolean('require_uppercase') ? '1' : '0';
        $data['require_numbers']      = $request->boolean('require_numbers') ? '1' : '0';
        $data['require_symbols']      = $request->boolean('require_symbols') ? '1' : '0';
        $data['exam_webcam_required'] = $request->boolean('exam_webcam_required') ? '1' : '0';

        Setting::setGroup('seguranca', $data);

        return back()->with('success', 'Configurações de segurança salvas com sucesso.');
    }

    public function acessos()
    {
        $this->ensureSuperAdmin();

        $systems = ClientSystem::active()->get();
        $users = User::query()
            ->whereIn('role', ['admin', 'professor', 'coordinator'])
            ->with('clientSystem:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        return view('configuracoes.acessos', compact('users', 'systems'));
    }

    public function salvarAcesso(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'cpf' => 'required|string|min:11|max:14',
            'password' => 'required|string|min:6|max:64',
            'profile' => 'required|in:administrador,criador_prova,comercial',
            'client_system_id' => 'nullable|exists:client_systems,id',
            'active' => 'nullable|boolean',
        ]);

        $role = $this->mapProfileToRole($data['profile']);
        $cpf = preg_replace('/\D+/', '', (string) $data['cpf']);

        User::query()->create([
            'client_system_id' => $data['client_system_id'] ? (int) $data['client_system_id'] : null,
            'role' => $role,
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'cpf' => $cpf,
            'password' => Hash::make($data['password']),
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'Perfil de acesso criado com sucesso.');
    }

    public function atualizarAcesso(Request $request, User $user)
    {
        $this->ensureSuperAdmin();

        abort_if($user->isSuperAdmin(), 422, 'Não é permitido alterar o Super Admin por esta tela.');

        $data = $request->validate([
            'profile' => 'required|in:administrador,criador_prova,comercial',
            'client_system_id' => 'nullable|exists:client_systems,id',
            'active' => 'nullable|boolean',
        ]);

        $user->update([
            'role' => $this->mapProfileToRole($data['profile']),
            'client_system_id' => $data['client_system_id'] ? (int) $data['client_system_id'] : null,
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Perfil de acesso atualizado com sucesso.');
    }

    // ── Backup e Manutenção ──────────────────────────────────────────

    public function backup()
    {
        $this->ensureSuperAdmin();

        $settings = Setting::getGroup('backup');
        $backupFiles = $this->maintenanceService->listBackups();
        $maintenance = $this->maintenanceService->summary();

        return view('configuracoes.backup', compact('settings', 'backupFiles', 'maintenance'));
    }

    public function salvarBackup(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'auto_backup'      => 'nullable|boolean',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'backup_retention' => 'required|integer|min:1|max:90',
        ]);

        $data['auto_backup'] = $request->boolean('auto_backup') ? '1' : '0';

        Setting::setGroup('backup', $data);

        return back()->with('success', 'Configurações de backup salvas com sucesso.');
    }

    public function executarBackup()
    {
        $this->ensureSuperAdmin();

        try {
            $backup = $this->maintenanceService->createBackupArchive();

            return back()->with('success', "Backup criado com sucesso: {$backup['name']} ({$backup['size']})");

            $timestamp = date('Y-m-d_His');
            $zipName = "backup_avaliafa_{$timestamp}.zip";
            $backupDir = storage_path('app/backups');

            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $zipPath = $backupDir . '/' . $zipName;
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return back()->with('error', 'Não foi possível criar o arquivo ZIP.');
            }

            // ── 1. Dump do banco de dados ───────────────────────────────
            $sqlDump = $this->gerarDumpSql();
            if ($sqlDump !== null) {
                $zip->addFromString("database/avaliafa_{$timestamp}.sql", $sqlDump);
            }

            // ── 2. Pastas principais do projeto ─────────────────────────
            $basePath = base_path();
            $folders = ['app', 'config', 'database', 'resources', 'routes', 'public'];

            foreach ($folders as $folder) {
                $folderPath = $basePath . '/' . $folder;
                if (is_dir($folderPath)) {
                    $this->addFolderToZip($zip, $folderPath, $folder);
                }
            }

            // ── 3. Arquivos raiz importantes ────────────────────────────
            $rootFiles = ['.env', '.env.example', 'composer.json', 'composer.lock', 'package.json', 'artisan'];
            foreach ($rootFiles as $file) {
                $filePath = $basePath . '/' . $file;
                if (is_file($filePath)) {
                    $zip->addFile($filePath, $file);
                }
            }

            $zip->close();

            $size = $this->formatBytes(filesize($zipPath));

            return back()->with('success', "Backup criado com sucesso: {$zipName} ({$size})");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao criar backup: ' . $e->getMessage());
        }
    }

    public function downloadBackup(string $filename)
    {
        $this->ensureSuperAdmin();

        $path = config('maintenance.backup_storage_path', storage_path('app/backups')).'/' . basename($filename);

        if (! is_file($path)) {
            return back()->with('error', 'Arquivo de backup não encontrado.');
        }

        return response()->download($path);
    }

    public function excluirBackup(string $filename)
    {
        $this->ensureSuperAdmin();

        $path = config('maintenance.backup_storage_path', storage_path('app/backups')).'/' . basename($filename);

        if (! is_file($path)) {
            return back()->with('error', 'Arquivo de backup não encontrado.');
        }

        unlink($path);

        return back()->with('success', 'Backup excluído com sucesso.');
    }

    public function limparCache()
    {
        $this->ensureSuperAdmin();

        $this->maintenanceService->clearCaches();

        return back()->with('success', 'Cache limpo com sucesso.');
    }

    // ── Moodle Sync ───────────────────────────────────────────────────

    public function moodle()
    {
        $this->ensureSuperAdmin();

        $pending = ExamSession::where('grade_published', true)
            ->where('moodle_synced', false)
            ->where('is_simulation', false)
            ->whereHas('exam')
            ->with(['exam:id,title,client_system_id', 'student:id,first_name,last_name,email,cpf', 'moodleSyncLogs' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        $totalSynced = ExamSession::where('grade_published', true)->where('moodle_synced', true)->where('is_simulation', false)->count();
        $totalPending = $pending->count();

        return view('configuracoes.moodle', compact('pending', 'totalSynced', 'totalPending'));
    }

    public function resyncAll(Request $request)
    {
        $this->ensureSuperAdmin();

        $sessions = ExamSession::where('grade_published', true)
            ->where('moodle_synced', false)
            ->where('is_simulation', false)
            ->with(['exam.clientSystem', 'student'])
            ->get();

        $service = app(MoodleSyncService::class);
        $results = ['success' => 0, 'failed' => 0, 'details' => []];

        foreach ($sessions as $session) {
            $ok = $service->sync($session);
            $results[$ok ? 'success' : 'failed']++;
            $results['details'][] = [
                'session_id' => $session->id,
                'student' => $session->student?->first_name,
                'exam' => $session->exam?->title,
                'success' => $ok,
            ];
        }

        return response()->json($results);
    }

    // ── LTI 1.3 ──────────────────────────────────────────────────────

    public function atualizarSistema(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'version' => 'required|string|max:60',
            'package' => [
                'required',
                'file',
                'mimes:zip',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value instanceof \Illuminate\Http\UploadedFile && (int) $value->getSize() <= 0) {
                        $fail('O arquivo ZIP enviado esta vazio. Envie um pacote valido.');
                    }
                },
            ],
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->maintenanceService->applyUpdate(
                $request->file('package'),
                $data['version'],
                $data['notes'] ?? null,
            );

            return redirect()
                ->route('configuracoes.backup')
                ->with('success', "Atualização {$result['version']} aplicada com sucesso. Backup preventivo: {$result['backup']}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao aplicar atualização: '.$e->getMessage());
        }
    }

    public function lti()
    {
        $this->ensureSuperAdmin();

        $settings = Setting::getGroup('lti');

        $privatePath = config('lti.private_key_path', storage_path('oauth-private.key'));
        $publicPath = config('lti.public_key_path', storage_path('oauth-public.key'));

        $keysExist = is_file($privatePath) && is_file($publicPath);
        $keyId = config('lti.tool_key_id', 'avaliafa-lti');

        $urls = [
            'login'        => route('lti.login'),
            'launch'       => route('lti.launch'),
            'jwks'         => route('lti.jwks'),
            'deep_linking' => route('lti.deep-linking'),
        ];

        $registrations = LtiRegistration::with('clientSystem')->get();
        $systems = ClientSystem::active()->get();
        $resourceLinks = LtiResourceLink::with(['registration', 'exam'])->latest()->get();
        $exams = Exam::whereIn('status', ['published', 'active', 'draft'])->orderBy('title')->get(['id', 'title', 'client_system_id', 'status']);

        return view('configuracoes.lti', compact('settings', 'keysExist', 'keyId', 'urls', 'registrations', 'systems', 'resourceLinks', 'exams'));
    }

    public function salvarLti(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'lti_enabled'   => 'nullable|boolean',
            'tool_key_id'   => 'nullable|string|max:100',
        ]);

        $data['lti_enabled'] = $request->boolean('lti_enabled') ? '1' : '0';
        $data['tool_key_id'] = $data['tool_key_id'] ?: 'avaliafa-lti';

        Setting::setGroup('lti', $data);

        return back()->with('success', 'Configurações LTI salvas com sucesso.');
    }

    public function salvarRegistroLti(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'client_system_id'  => 'required|exists:client_systems,id',
            'issuer'            => 'required|url|max:500',
            'client_id'         => 'required|string|max:255',
            'deployment_id'     => 'nullable|string|max:255',
            'platform_name'     => 'nullable|string|max:255',
            'auth_login_url'    => 'required|url|max:500',
            'auth_token_url'    => 'required|url|max:500',
            'keyset_url'        => 'required|url|max:500',
            'active'            => 'nullable|boolean',
        ]);

        $data['active'] = $request->boolean('active');

        $registrationId = $request->input('registration_id');

        if ($registrationId) {
            $registration = LtiRegistration::findOrFail($registrationId);
            $registration->update($data);
            $msg = 'Registro LTI atualizado com sucesso.';
        } else {
            LtiRegistration::create($data);
            $msg = 'Registro LTI criado com sucesso.';
        }

        // Sync to ClientSystem moodle_config.lti for backward compat
        $system = ClientSystem::find($data['client_system_id']);
        if ($system) {
            $moodle = $system->moodle_config ?? [];
            $moodle['lti'] = [
                'enabled'             => true,
                'issuer'              => $data['issuer'],
                'client_id'           => $data['client_id'],
                'deployment_id'       => $data['deployment_id'] ?? '',
                'platform_login_url'  => $data['auth_login_url'],
                'platform_token_url'  => $data['auth_token_url'],
                'platform_keyset_url' => $data['keyset_url'],
            ];
            $system->moodle_config = $moodle;
            $system->save();
        }

        return back()->with('success', $msg);
    }

    public function excluirRegistroLti(LtiRegistration $registration)
    {
        $this->ensureSuperAdmin();

        $registration->delete();

        return back()->with('success', 'Registro LTI excluído com sucesso.');
    }

    public function excluirResourceLink(LtiResourceLink $resourceLink)
    {
        $this->ensureSuperAdmin();

        $resourceLink->delete();

        return back()->with('success', 'Vinculo de atividade excluido com sucesso.');
    }

    public function gerarChavesLti()
    {
        $this->ensureSuperAdmin();

        $privatePath = config('lti.private_key_path', storage_path('oauth-private.key'));
        $publicPath = config('lti.public_key_path', storage_path('oauth-public.key'));

        if (is_file($privatePath) && is_file($publicPath)) {
            return back()->with('error', 'Chaves RSA já existem. Exclua manualmente para regenerar.');
        }

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            return back()->with('error', 'Falha ao gerar chave RSA: ' . openssl_error_string());
        }

        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);
        $publicKey = $details['key'];

        file_put_contents($privatePath, $privateKey);
        file_put_contents($publicPath, $publicKey);

        return back()->with('success', 'Par de chaves RSA gerado com sucesso.');
    }

    /**
     * Gera o dump SQL usando mysqldump (XAMPP) ou fallback via PDO.
     */
    private function gerarDumpSql(): ?string
    {
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Tenta caminhos comuns do mysqldump
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
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
                escapeshellarg($dbName)
            );

            $output = [];
            $returnCode = -1;
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode === 0 && ! empty($output)) {
                return implode("\n", $output);
            }
        }

        // Fallback: dump via PDO (estrutura + dados)
        return $this->gerarDumpPdo($dbName);
    }

    /**
     * Fallback: gera dump SQL via PDO quando mysqldump não está disponível.
     */
    private function gerarDumpPdo(string $dbName): ?string
    {
        try {
            $pdo = \DB::connection()->getPdo();
            $lines = [];
            $lines[] = "-- AvaliaFA Database Backup";
            $lines[] = "-- Gerado em: " . date('Y-m-d H:i:s');
            $lines[] = "-- Método: PDO fallback";
            $lines[] = "-- Banco: {$dbName}";
            $lines[] = "";
            $lines[] = "SET FOREIGN_KEY_CHECKS=0;";
            $lines[] = "";

            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // CREATE TABLE
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
                $lines[] = $create['Create Table'] . ";";
                $lines[] = "";

                // INSERT dados
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
                if (! empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $colList = '`' . implode('`, `', $columns) . '`';

                    foreach (array_chunk($rows, 500) as $chunk) {
                        $values = [];
                        foreach ($chunk as $row) {
                            $vals = array_map(function ($v) use ($pdo) {
                                return $v === null ? 'NULL' : $pdo->quote($v);
                            }, array_values($row));
                            $values[] = '(' . implode(', ', $vals) . ')';
                        }
                        $lines[] = "INSERT INTO `{$table}` ({$colList}) VALUES";
                        $lines[] = implode(",\n", $values) . ";";
                    }
                    $lines[] = "";
                }
            }

            $lines[] = "SET FOREIGN_KEY_CHECKS=1;";

            return implode("\n", $lines);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Adiciona uma pasta recursivamente ao ZIP, ignorando vendor/node_modules/storage.
     */
    private function addFolderToZip(\ZipArchive $zip, string $folderPath, string $zipPrefix): void
    {
        $ignoreDirs = ['vendor', 'node_modules', '.git', 'storage'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS),
                function ($current, $key, $iterator) use ($ignoreDirs) {
                    if ($current->isDir() && in_array($current->getFilename(), $ignoreDirs)) {
                        return false;
                    }
                    return true;
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = $zipPrefix . '/' . substr($file->getPathname(), strlen($folderPath) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile() && $file->getSize() < 10 * 1024 * 1024) { // max 10MB por arquivo
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    private function listBackups(): array
    {
        $path = storage_path('app/backups');

        if (! is_dir($path)) {
            return [];
        }

        $files = glob($path . '/*.zip');
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i', filemtime($file)),
            ];
        }

        usort($result, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $result;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function mapProfileToRole(string $profile): string
    {
        return match ($profile) {
            'administrador' => 'admin',
            'criador_prova' => 'professor',
            'comercial' => 'coordinator',
            default => 'student',
        };
    }
}

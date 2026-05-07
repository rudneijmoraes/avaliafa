<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Models\User;
use App\Services\MoodleActivityResolver;
use App\Services\MoodleSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MoodleDiagnoseCommand extends Command
{
    protected $signature = 'moodle:diagnose
        {--cpf= : CPF do estudante (11 dígitos)}
        {--prova= : Título parcial da prova (ex: "Prova 0060")}
        {--session= : ID direto da sessão}
        {--resync : Executar re-sync da nota após diagnóstico}
        {--probe : Consultar boletim do Moodle para verificar grade items}';

    protected $description = 'Diagnostica e opcionalmente re-sincroniza nota com Moodle';

    public function handle(): int
    {
        $session = $this->resolveSession();

        if (! $session) {
            $this->error('Sessão não encontrada com os filtros informados.');

            return self::FAILURE;
        }

        $session->loadMissing(['exam.clientSystem', 'student']);

        $this->info('');
        $this->info('═══ DIAGNÓSTICO MOODLE ═══');
        $this->info('');

        // Sessão
        $this->line("Sessão ID:      {$session->id}");
        $this->line("Status:         {$session->status}");
        $this->line("Nota final:     {$session->final_score}");
        $this->line("Grade published: ".($session->grade_published ? 'SIM' : 'NÃO'));
        $this->line("Moodle synced:  ".($session->moodle_synced ? 'SIM' : 'NÃO'));
        $this->line("Simulação:      ".($session->is_simulation ? 'SIM' : 'NÃO'));
        $this->info('');

        // Estudante
        $student = $session->student;
        $this->info('── Estudante ──');
        $this->line("Nome:             {$student?->name}");
        $this->line("CPF:              {$student?->cpf}");
        $this->line("moodle_user_id:   ".($student?->moodle_user_id ?: '(VAZIO)'));
        $this->info('');

        // Prova
        $exam = $session->exam;
        $this->info('── Prova ──');
        $this->line("Título:     {$exam->title}");
        $this->line("Exam ID:    {$exam->id}");
        $this->line("Settings:   ".json_encode($exam->settings ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info('');

        // Sistema
        $system = $exam->clientSystem;
        $this->info('── Sistema (ClientSystem) ──');
        $this->line("Nome:              {$system?->name}");
        $this->line("Slug:              {$system?->slug}");
        $this->line("Moodle URL:        ".($system?->getMoodleUrl() ?: '(VAZIO)'));
        $this->line("Token presente:    ".(! empty($system?->moodle_config['token']) ? 'SIM ('.strlen($system->moodle_config['token']).' chars)' : 'NÃO'));
        $this->line("hasMoodleInteg.:   ".($system?->hasMoodleIntegration() ? 'SIM' : 'NÃO'));

        $moodleConfig = $system?->moodle_config ?? [];
        $safeConfig = $moodleConfig;
        if (isset($safeConfig['token'])) {
            $safeConfig['token'] = substr($safeConfig['token'], 0, 8).'...';
        }
        $this->line("moodle_config:     ".json_encode($safeConfig, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info('');

        // Resolver
        $this->info('── MoodleActivityResolver ──');
        $resolver = app(MoodleActivityResolver::class);
        $resolved = $resolver->resolveForSession($session);

        if ($resolved === null) {
            $this->error('Resolver retornou NULL — sem URL ou token configurados.');
            $this->error('A nota NÃO será enviada ao Moodle enquanto isto não for corrigido.');

            return self::FAILURE;
        }

        $this->line("URL:          {$resolved['url']}");
        $this->line("course_id:    {$resolved['course_id']}");
        $this->line("activity_id:  {$resolved['activity_id']}");
        $this->line("component:    {$resolved['component']}");
        $this->line("scale:        {$resolved['scale']}");
        $this->line("itemnumber:   {$resolved['itemnumber']}");
        $this->info('');

        // Verificar bloqueios
        $this->info('── Verificação de Bloqueios ──');
        $blockers = [];

        if ($session->is_simulation) {
            $blockers[] = 'Sessão é simulação — sync ignorado';
        }
        if (! $system?->hasMoodleIntegration()) {
            $blockers[] = 'ClientSystem sem integração Moodle (url + token)';
        }
        if (($resolved['course_id'] ?? 0) <= 0) {
            $blockers[] = 'course_id = 0 (não configurado)';
        }
        if (($resolved['activity_id'] ?? 0) <= 0) {
            $blockers[] = 'activity_id = 0 (não encontrado)';
        }
        if (empty($student?->moodle_user_id)) {
            $blockers[] = 'Estudante sem moodle_user_id — Moodle não sabe para quem enviar';
        }
        if ($session->final_score === null) {
            $blockers[] = 'Sessão sem nota final (final_score = null)';
        }

        if (empty($blockers)) {
            $this->info('Nenhum bloqueio detectado — sync deveria funcionar.');
        } else {
            foreach ($blockers as $b) {
                $this->error("BLOQUEIO: {$b}");
            }
            $this->info('');
            $this->warn('Corrija os bloqueios acima antes de tentar o re-sync.');

            if (! $this->option('resync') && ! $this->option('probe')) {
                return self::FAILURE;
            }
        }

        // Logs de sync anteriores
        $this->info('');
        $this->info('── Logs de Sync Anteriores ──');
        $logs = \App\Models\MoodleSyncLog::where('session_id', $session->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($logs->isEmpty()) {
            $this->warn('Nenhum log de sync encontrado para esta sessão.');
        } else {
            foreach ($logs as $log) {
                $this->line("  [{$log->created_at}] status={$log->status} grade={$log->grade_sent} retry={$log->retry_count} erro={$log->error_message}");
            }
        }

        // Probe — consultar Moodle para detalhes do módulo e grade items
        if ($this->option('probe')) {
            $this->probeMoodle($resolved, $student);
        }

        // Re-sync
        if ($this->option('resync')) {
            $this->info('');
            $this->info('══ EXECUTANDO RE-SYNC ══');

            if (! empty($blockers)) {
                $this->warn('Tentando mesmo com bloqueios detectados...');
            }

            $service = app(MoodleSyncService::class);
            $result = $service->sync($session);

            $session->refresh();

            if ($result) {
                $this->info('SUCESSO — Nota enviada ao Moodle!');
                $this->line("moodle_synced: ".($session->moodle_synced ? 'SIM' : 'NÃO'));
            } else {
                $this->error('FALHA — Verifique os logs acima e o storage/logs/laravel.log');

                $lastLog = \App\Models\MoodleSyncLog::where('session_id', $session->id)
                    ->orderByDesc('created_at')
                    ->first();
                if ($lastLog) {
                    $this->error("Último erro: {$lastLog->error_message}");
                }
            }
        } elseif (! $this->option('probe')) {
            $this->info('');
            $this->info('Para tentar re-sincronizar, adicione --resync ao comando:');
            $this->line("  php artisan moodle:diagnose --session={$session->id} --resync");
        }

        return self::SUCCESS;
    }

    private function probeMoodle(array $moodle, ?User $student): void
    {
        $this->info('');
        $this->info('══ SONDA MOODLE ══');

        $url = $moodle['url'];
        $token = $moodle['token'];
        $courseId = $moodle['course_id'];
        $cmid = $moodle['activity_id'];
        $moodleUserId = $student?->moodle_user_id;

        // 1. Listar módulos do curso
        $this->info('');
        $this->info('── Módulos do curso (cmid, instance, modname, name) ──');
        $response = Http::asForm()->post("{$url}/webservice/rest/server.php", [
            'wstoken' => $token,
            'wsfunction' => 'core_course_get_contents',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId,
        ]);

        $targetModule = null;

        if ($response->successful() && is_array($response->json())) {
            foreach ($response->json() as $section) {
                foreach (($section['modules'] ?? []) as $module) {
                    $mark = ((int) ($module['id'] ?? 0) === $cmid) ? ' <<<' : '';
                    $this->line("  cmid={$module['id']}  instance={$module['instance']}  mod={$module['modname']}  \"{$module['name']}\"{$mark}");

                    if ((int) ($module['id'] ?? 0) === $cmid) {
                        $targetModule = $module;
                    }
                }
            }
        } else {
            $this->error('Falha ao buscar conteúdo do curso: '.mb_substr($response->body(), 0, 200));
        }

        if ($targetModule) {
            $this->info('');
            $this->info('── Módulo alvo (cmid='.$cmid.') ──');
            $this->line("  modname:   {$targetModule['modname']}");
            $this->line("  instance:  {$targetModule['instance']}");
            $this->line("  name:      {$targetModule['name']}");
            $this->line("  url:       ".($targetModule['url'] ?? 'N/A'));
        }

        // 2. Consultar boletim do aluno
        if ($moodleUserId) {
            $this->info('');
            $this->info("── Boletim do aluno (moodle_user_id={$moodleUserId}) ──");

            $response = Http::asForm()->post("{$url}/webservice/rest/server.php", [
                'wstoken' => $token,
                'wsfunction' => 'gradereport_user_get_grades_table',
                'moodlewsrestformat' => 'json',
                'courseid' => $courseId,
                'userid' => $moodleUserId,
            ]);

            if ($response->successful()) {
                $payload = $response->json();

                if (isset($payload['exception'])) {
                    $this->error("Erro: {$payload['errorcode']} — {$payload['message']}");
                } else {
                    $tables = $payload['tables'] ?? [];
                    foreach ($tables as $table) {
                        $this->line("  Tabela: ".($table['courseid'] ?? '?')." / max_depth=".($table['maxdepth'] ?? '?'));

                        foreach (($table['tabledata'] ?? []) as $row) {
                            if (! is_array($row)) {
                                continue;
                            }

                            $itemName = strip_tags($row['itemname']['content'] ?? '—');
                            $grade = strip_tags($row['grade']['content'] ?? '—');
                            $range = strip_tags($row['range']['content'] ?? '—');
                            $percentage = strip_tags($row['percentage']['content'] ?? '—');

                            $this->line("    {$itemName}  |  nota={$grade}  |  range={$range}  |  %={$percentage}");
                        }
                    }
                }
            } else {
                $this->error('Falha ao buscar boletim: '.mb_substr($response->body(), 0, 200));
            }

            // 3. Consultar notas do curso para este aluno
            $this->info('');
            $this->info("── Nota geral do curso ──");

            $response = Http::asForm()->post("{$url}/webservice/rest/server.php", [
                'wstoken' => $token,
                'wsfunction' => 'gradereport_overview_get_course_grades',
                'moodlewsrestformat' => 'json',
                'userid' => $moodleUserId,
            ]);

            if ($response->successful()) {
                $payload = $response->json();

                if (isset($payload['exception'])) {
                    $this->error("Erro: {$payload['errorcode']} — {$payload['message']}");
                } else {
                    $grades = $payload['grades'] ?? [];
                    foreach ($grades as $g) {
                        if (((int) ($g['courseid'] ?? 0)) === $courseId) {
                            $this->line("  course={$g['courseid']}  grade={$g['grade']}  rawgrade={$g['rawgrade']}  rank={$g['rank']}");
                        }
                    }

                    if (empty($grades)) {
                        $this->warn('  Nenhuma nota encontrada.');
                    }
                }
            } else {
                $this->error('Falha: '.mb_substr($response->body(), 0, 200));
            }
        }

        // 4. Teste rápido: tentar core_grades_update_grades com mod_lti e instance
        if ($targetModule) {
            $this->info('');
            $this->info('── Teste: core_grades_update_grades com diferentes parâmetros ──');

            $instanceId = (int) ($targetModule['instance'] ?? 0);
            $modname = $targetModule['modname'] ?? 'lti';

            // Teste A: mod_{modname} + instance_id
            $this->line("  Teste A: component=mod_{$modname}, activityid={$instanceId}");
            $resultA = $this->testGradeUpdate($url, $token, $courseId, "mod_{$modname}", $instanceId, $moodleUserId, 10.0);
            $this->line("    → {$resultA}");

            // Teste B: mod_{modname} + cmid
            $this->line("  Teste B: component=mod_{$modname}, activityid={$cmid} (cmid)");
            $resultB = $this->testGradeUpdate($url, $token, $courseId, "mod_{$modname}", $cmid, $moodleUserId, 10.0);
            $this->line("    → {$resultB}");

            // Teste C: mod_assign + instance_id
            $this->line("  Teste C: component=mod_assign, activityid={$instanceId}");
            $resultC = $this->testGradeUpdate($url, $token, $courseId, 'mod_assign', $instanceId, $moodleUserId, 10.0);
            $this->line("    → {$resultC}");
        }
    }

    private function testGradeUpdate(string $url, string $token, int $courseId, string $component, int $activityId, ?string $studentId, float $grade): string
    {
        if (! $studentId) {
            return 'SKIP — sem moodle_user_id';
        }

        try {
            $response = Http::asForm()->post("{$url}/webservice/rest/server.php", [
                'wstoken' => $token,
                'wsfunction' => 'core_grades_update_grades',
                'moodlewsrestformat' => 'json',
                'source' => 'AvaliaFA',
                'courseid' => $courseId,
                'component' => $component,
                'activityid' => $activityId,
                'itemnumber' => 0,
                'grades[0][studentid]' => $studentId,
                'grades[0][grade]' => $grade,
            ]);

            $payload = $response->json();

            if (is_array($payload) && isset($payload['exception'])) {
                return "ERRO: {$payload['errorcode']} — {$payload['message']}";
            }

            if ($payload === 0 || $payload === null || $payload === '0' || (is_string($payload) && trim($payload) === '')) {
                return 'SUCESSO (GRADE_UPDATE_OK = 0)';
            }

            return 'Resposta: '.json_encode($payload);
        } catch (\Throwable $e) {
            return "EXCEPTION: {$e->getMessage()}";
        }
    }

    private function resolveSession(): ?ExamSession
    {
        if ($this->option('session')) {
            return ExamSession::find($this->option('session'));
        }

        if (! $this->option('cpf') || ! $this->option('prova')) {
            $this->error('Informe --cpf e --prova, ou --session');

            return null;
        }

        $cpf = preg_replace('/\D/', '', $this->option('cpf'));
        $student = User::where('cpf', $cpf)->where('role', 'student')->first();

        if (! $student) {
            $this->error("Estudante com CPF {$cpf} não encontrado.");

            return null;
        }

        $this->line("Estudante encontrado: {$student->name} (ID: {$student->id})");

        $provaSearch = $this->option('prova');

        $session = ExamSession::where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q->where('title', 'like', "%{$provaSearch}%"))
            ->where('status', 'graded')
            ->orderByDesc('created_at')
            ->first();

        if (! $session) {
            $session = ExamSession::where('student_id', $student->id)
                ->whereHas('exam', fn ($q) => $q->where('title', 'like', "%{$provaSearch}%"))
                ->orderByDesc('created_at')
                ->first();
        }

        if ($session) {
            $session->loadMissing('exam');
            $this->line("Sessão encontrada: ID {$session->id} — Prova: {$session->exam->title} — Status: {$session->status}");
        }

        return $session;
    }
}

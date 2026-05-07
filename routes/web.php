<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\CertificadoController;
use App\Http\Controllers\Web\ConfiguracaoController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DemoController;
use App\Http\Controllers\Web\EstudanteController;
use App\Http\Controllers\Web\ExamWebController;
use App\Http\Controllers\Web\LtiToolController;
use App\Http\Controllers\Web\MediaController;
use App\Http\Controllers\Web\OperacoesController;
use App\Http\Controllers\Web\PerfilController;
use App\Http\Controllers\Web\ProvaController;
use App\Http\Controllers\Web\QuestaoController;
use App\Http\Controllers\Web\RelatorioController;
use App\Http\Controllers\Web\SimuladoAlunoController;
use App\Http\Controllers\Web\SimuladoController;
use App\Http\Controllers\Web\SimuladoEmailTemplateController;
use App\Http\Controllers\Web\SimuladoHubController;
use App\Http\Controllers\Web\SimuladoPublicController;
use App\Http\Controllers\Web\SimuladoRankingController;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Controllers\Web\SistemaController;
use App\Http\Controllers\Web\StudentGradeController;
use App\Http\Controllers\Web\StudentMaterialController;
use App\Http\Controllers\Web\StudentTipController;
use App\Http\Controllers\Web\TeacherTipController;
use App\Http\Controllers\Web\LearningMaterialController;
use Illuminate\Support\Facades\Route;

Route::get('/assets/secure-exam-engine.js', function () {
    $path = resource_path('js/secure-exam-engine.js');
    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
})->name('assets.secure-exam-engine');

Route::get('/media/profile-photo/{user}', [MediaController::class, 'profilePhoto'])
    ->name('media.profile-photo');
Route::get('/media/snapshots/{snapshot}', [MediaController::class, 'snapshot'])
    ->middleware('auth')
    ->name('media.snapshot');

Route::match(['GET', 'POST'], '/lti/login', [LtiToolController::class, 'login'])->name('lti.login');
Route::match(['GET', 'POST'], '/lti/launch', [LtiToolController::class, 'launch'])->name('lti.launch');
Route::get('/lti/jwks', [LtiToolController::class, 'jwks'])->name('lti.jwks');
Route::post('/lti/deep-linking', [LtiToolController::class, 'deepLinking'])->name('lti.deep-linking');
Route::post('/lti/map-resource', [LtiToolController::class, 'mapResource'])->name('lti.map-resource');

// ── Autenticação ──────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthWebController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ── Área autenticada ──────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('minhas-notas')->middleware('role:student')->name('student.grades.')->group(function () {
        Route::get('/', [StudentGradeController::class, 'index'])->name('index');
        Route::get('/{session}', [StudentGradeController::class, 'show'])->name('show');
    });

    // Portal do aluno — dicas e materiais
    Route::middleware('role:student')->group(function () {
        Route::get('/dicas', [StudentTipController::class, 'index'])->name('student.tips.index');
        Route::get('/materiais', [StudentMaterialController::class, 'index'])->name('student.materials.index');
    });

    // Perfil
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::get('/perfil/senha', [PerfilController::class, 'showSenha'])->name('perfil.senha');
    Route::put('/perfil/senha', [PerfilController::class, 'updateSenha'])->name('perfil.senha.update');
    Route::post('/perfil/foto', [PerfilController::class, 'atualizarFoto'])->name('perfil.foto');

    Route::middleware('role:super_admin,admin,professor')->group(function () {
        // Provas (CRUD completo + ações extras)
        Route::resource('provas', ProvaController::class)->parameters(['provas' => 'exam']);
        // Modo demonstração
        Route::get('/demo/prova/{exam}', [DemoController::class, 'startExamDemo'])->name('demo.exam');
        Route::get('/demo/simulado/{simulado}', [DemoController::class, 'startSimuladoDemo'])->whereNumber('simulado')->name('demo.simulado');

        Route::post('/provas/{exam}/publicar', [ProvaController::class, 'publish'])->name('provas.publish');
        Route::post('/provas/{exam}/arquivar', [ProvaController::class, 'archive'])->name('provas.archive');
        Route::post('/provas/{exam}/blocos', [ProvaController::class, 'storeBlock'])->name('provas.blocks.store');
        Route::put('/provas/{exam}/blocos/{block}', [ProvaController::class, 'updateBlock'])->name('provas.blocks.update');
        Route::delete('/provas/{exam}/blocos/{block}', [ProvaController::class, 'destroyBlock'])->name('provas.blocks.destroy');
        Route::get('/provas/{exam}/questoes/buscar', [ProvaController::class, 'searchQuestions'])->name('provas.questions.search');
        Route::post('/provas/{exam}/questoes', [ProvaController::class, 'attachQuestion'])->name('provas.questions.store');
        Route::delete('/provas/{exam}/questoes/{question}', [ProvaController::class, 'detachQuestion'])->name('provas.questions.destroy');
        Route::post('/provas/{exam}/gerar-link', [ProvaController::class, 'generateLink'])->name('provas.generate-link');
        Route::post('/provas/{exam}/gerar-links-moodle', [ProvaController::class, 'generateBulkLinks'])->name('provas.generate-bulk-links');

        // Conteúdo: dicas do professor e materiais de aprendizagem
        Route::prefix('conteudo')->name('conteudo.')->group(function () {
            // Dicas do professor
            Route::prefix('dicas')->name('dicas.')->group(function () {
                Route::patch('/reordenar', [TeacherTipController::class, 'reorder'])->name('reorder');
                Route::get('/', [TeacherTipController::class, 'index'])->name('index');
                Route::get('/criar', [TeacherTipController::class, 'create'])->name('create');
                Route::post('/', [TeacherTipController::class, 'store'])->name('store');
                Route::get('/{tip}/editar', [TeacherTipController::class, 'edit'])->name('edit');
                Route::put('/{tip}', [TeacherTipController::class, 'update'])->name('update');
                Route::delete('/{tip}', [TeacherTipController::class, 'destroy'])->name('destroy');
            });

            // Materiais de aprendizagem
            Route::prefix('materiais')->name('materiais.')->group(function () {
                Route::get('/', [LearningMaterialController::class, 'index'])->name('index');
                Route::get('/criar', [LearningMaterialController::class, 'create'])->name('create');
                Route::post('/', [LearningMaterialController::class, 'store'])->name('store');
                Route::get('/{material}/editar', [LearningMaterialController::class, 'edit'])->name('edit');
                Route::put('/{material}', [LearningMaterialController::class, 'update'])->name('update');
                Route::delete('/{material}', [LearningMaterialController::class, 'destroy'])->name('destroy');
            });
        });

        // Questões
        Route::get('/questoes/importar', [QuestaoController::class, 'importForm'])->name('questoes.import.form');
        Route::post('/questoes/importar', [QuestaoController::class, 'import'])->name('questoes.import');
        Route::get('/questoes/importar/template-csv', [QuestaoController::class, 'downloadTemplateCsv'])->name('questoes.import.template.csv');
        Route::get('/questoes/importar/template-csv-lote', [QuestaoController::class, 'downloadTemplateCsvBulk'])->name('questoes.import.template.csv.bulk');
        Route::get('/questoes/importar/guia', [QuestaoController::class, 'downloadImportGuide'])->name('questoes.import.guide');
        Route::get('/questoes/importar/guia-avancado', [QuestaoController::class, 'downloadImportGuideAdvanced'])->name('questoes.import.guide.advanced');
        Route::get('/questoes/importar/template-xml', [QuestaoController::class, 'downloadTemplateXml'])->name('questoes.import.template.xml');
        Route::resource('questoes', QuestaoController::class)->parameters(['questoes' => 'questao']);
        Route::post('/questoes/upload-imagem', [QuestaoController::class, 'uploadImagem'])->name('questoes.upload-imagem');
    });

    Route::middleware('role:super_admin,admin')->group(function () {
        // Estudantes
        Route::get('/estudantes/importar', [EstudanteController::class, 'importForm'])->name('estudantes.import.form');
        Route::post('/estudantes/importar', [EstudanteController::class, 'import'])->name('estudantes.import');
        Route::get('/estudantes/importar/template', [EstudanteController::class, 'downloadTemplate'])->name('estudantes.import.template');
        Route::resource('estudantes', EstudanteController::class);
        Route::post('/estudantes/{estudante}/foto', [EstudanteController::class, 'atualizarFoto'])->name('estudantes.foto');
        Route::post('/estudantes/{estudante}/reset-senha', [EstudanteController::class, 'resetSenha'])->name('estudantes.reset-senha');
        Route::post('/estudantes/{estudante}/face-reference', [EstudanteController::class, 'updateFaceReference'])->name('estudantes.face-reference.update');
        Route::delete('/estudantes/{estudante}/face-reference', [EstudanteController::class, 'destroyFaceReference'])->name('estudantes.face-reference.destroy');
        Route::get('/certificados', [CertificadoController::class, 'index'])->name('certificados.index');
        Route::get('/certificados/{certificado}/download', [CertificadoController::class, 'download'])->name('certificados.download');
        Route::patch('/certificados/{certificado}/status', [CertificadoController::class, 'toggleStatus'])->name('certificados.status');
    });

    Route::middleware('role:super_admin,admin,professor,coordinator')->group(function () {
        Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
        Route::get('/relatorios/simulados/geral', [RelatorioController::class, 'showGeneralSimulations'])->name('relatorios.simulados.geral');
        Route::get('/relatorios/simulados/geral/exportar', [RelatorioController::class, 'exportGeneralSimulationsCsv'])->name('relatorios.simulados.geral.export');
        Route::get('/relatorios/provas/{exam}', [RelatorioController::class, 'showExam'])->name('relatorios.prova');
        Route::get('/relatorios/provas/{exam}/exportar', [RelatorioController::class, 'exportExamCsv'])->name('relatorios.prova.export');
        Route::patch('/relatorios/provas/{exam}/sessoes/{session}/nota', [RelatorioController::class, 'updateGrade'])->name('relatorios.prova.grade.update');
    });

    Route::prefix('simulados')->name('simulados.')->group(function () {
        Route::middleware('role:super_admin,admin,professor,coordinator')->group(function () {
            Route::get('/', [SimuladoController::class, 'index'])->name('index');
            Route::get('/{simulado}', [SimuladoController::class, 'show'])->whereNumber('simulado')->name('show');
            Route::get('/{simulado}/exportar/excel', [SimuladoController::class, 'exportExcel'])->whereNumber('simulado')->name('export.excel');
            Route::get('/{simulado}/exportar/pdf', [SimuladoController::class, 'exportPdf'])->whereNumber('simulado')->name('export.pdf');
            Route::get('/{simulado}/participantes/{participant}/pdf', [SimuladoController::class, 'exportParticipantPdf'])->whereNumber('simulado')->whereNumber('participant')->name('participants.export.pdf');
        });

        Route::middleware('role:super_admin,admin,professor')->group(function () {
            Route::prefix('hubs')->name('hubs.')->group(function () {
                Route::get('/', [SimuladoHubController::class, 'index'])->name('index');
                Route::get('/criar', [SimuladoHubController::class, 'create'])->name('create');
                Route::post('/', [SimuladoHubController::class, 'store'])->name('store');
                Route::get('/{hub}', [SimuladoHubController::class, 'show'])->whereNumber('hub')->name('show');
                Route::get('/{hub}/editar', [SimuladoHubController::class, 'edit'])->whereNumber('hub')->name('edit');
                Route::put('/{hub}', [SimuladoHubController::class, 'update'])->whereNumber('hub')->name('update');
            });

            Route::get('/criar', [SimuladoController::class, 'create'])->name('create');
            Route::post('/', [SimuladoController::class, 'store'])->name('store');
            Route::get('/{simulado}/editar', [SimuladoController::class, 'edit'])->whereNumber('simulado')->name('edit');
            Route::put('/{simulado}', [SimuladoController::class, 'update'])->whereNumber('simulado')->name('update');
            Route::delete('/{simulado}', [SimuladoController::class, 'destroy'])->whereNumber('simulado')->name('destroy');
            Route::post('/{simulado}/duplicar', [SimuladoController::class, 'duplicate'])->whereNumber('simulado')->name('duplicate');
            Route::get('/{simulado}/participantes/{participant}/editar', [SimuladoController::class, 'editParticipant'])->whereNumber('simulado')->whereNumber('participant')->name('participants.edit');
            Route::put('/{simulado}/participantes/{participant}', [SimuladoController::class, 'updateParticipant'])->whereNumber('simulado')->whereNumber('participant')->name('participants.update');
            Route::delete('/{simulado}/participantes/{participant}', [SimuladoController::class, 'destroyParticipant'])->whereNumber('simulado')->whereNumber('participant')->name('participants.destroy');
            Route::patch('/{simulado}/status', [SimuladoController::class, 'toggleStatus'])->whereNumber('simulado')->name('status');
            Route::post('/{simulado}/notify-pending', [SimuladoController::class, 'notifyPending'])->whereNumber('simulado')->name('notify-pending');
            Route::post('/{simulado}/broadcast-all', [SimuladoController::class, 'broadcastAll'])->whereNumber('simulado')->name('broadcast-all');
            Route::post('/{simulado}/participantes/{participant}/notify', [SimuladoController::class, 'notifyOne'])->whereNumber('simulado')->whereNumber('participant')->name('participants.notify');

            Route::prefix('templates')->name('templates.')->group(function () {
                Route::get('/', [SimuladoEmailTemplateController::class, 'index'])->name('index');
                Route::get('/criar', [SimuladoEmailTemplateController::class, 'create'])->name('create');
                Route::post('/', [SimuladoEmailTemplateController::class, 'store'])->name('store');
                Route::post('/testar', [SimuladoEmailTemplateController::class, 'testEmail'])->name('test-email');
                Route::get('/{template}/editar', [SimuladoEmailTemplateController::class, 'edit'])->name('edit');
                Route::put('/{template}', [SimuladoEmailTemplateController::class, 'update'])->name('update');
            });
        });
    });

    Route::get('/simulados-minha-area', [SimuladoAlunoController::class, 'index'])
        ->name('simulados.minha-area');
    Route::get('/simulados-minha-area/{slug}', [SimuladoAlunoController::class, 'showHub'])
        ->name('simulados.minha-area.hub');
    Route::get('/simulados/ranking', [SimuladoRankingController::class, 'index'])
        ->name('simulados.ranking');
    Route::get('/simulados/{simulado}/revisao-pdf', [SimuladoAlunoController::class, 'reviewPdf'])
        ->whereNumber('simulado')
        ->name('simulados.review-pdf');
    Route::get('/simulados/notificacoes', [SimuladoAlunoController::class, 'notifications'])
        ->name('simulados.notifications');
    Route::post('/simulados/{simulado}/fazer-novamente', [SimuladoAlunoController::class, 'retry'])
        ->whereNumber('simulado')
        ->name('simulados.retry');

    Route::resource('sistemas', SistemaController::class)
        ->middleware('role:super_admin')
        ->except(['show', 'destroy']);
    Route::post('/sistemas/moodle-courses', [SistemaController::class, 'fetchMoodleCourses'])
        ->middleware('role:super_admin')
        ->name('sistemas.moodle-courses');

    // Configuracoes (super_admin)
    Route::prefix('configuracoes')->middleware('role:super_admin')->name('configuracoes.')->group(function () {
        Route::get('/', [ConfiguracaoController::class, 'index'])->name('index');

        Route::get('/geral', [ConfiguracaoController::class, 'geral'])->name('geral');
        Route::post('/geral', [ConfiguracaoController::class, 'salvarGeral'])->name('geral.salvar');

        Route::get('/email', [ConfiguracaoController::class, 'email'])->name('email');
        Route::post('/email', [ConfiguracaoController::class, 'salvarEmail'])->name('email.salvar');
        Route::post('/email/testar', [ConfiguracaoController::class, 'testarEmail'])->name('email.testar');

        Route::get('/seguranca', [ConfiguracaoController::class, 'seguranca'])->name('seguranca');
        Route::post('/seguranca', [ConfiguracaoController::class, 'salvarSeguranca'])->name('seguranca.salvar');
        Route::get('/acessos', [ConfiguracaoController::class, 'acessos'])->name('acessos');
        Route::post('/acessos', [ConfiguracaoController::class, 'salvarAcesso'])->name('acessos.salvar');
        Route::patch('/acessos/{user}', [ConfiguracaoController::class, 'atualizarAcesso'])->name('acessos.atualizar');

        Route::get('/backup', [ConfiguracaoController::class, 'backup'])->name('backup');
        Route::post('/backup', [ConfiguracaoController::class, 'salvarBackup'])->name('backup.salvar');
        Route::post('/backup/executar', [ConfiguracaoController::class, 'executarBackup'])->name('backup.executar');
        Route::post('/backup/atualizar', [ConfiguracaoController::class, 'atualizarSistema'])->name('backup.atualizar');
        Route::get('/backup/{filename}/download', [ConfiguracaoController::class, 'downloadBackup'])->name('backup.download');
        Route::delete('/backup/{filename}', [ConfiguracaoController::class, 'excluirBackup'])->name('backup.excluir');
        Route::post('/cache/limpar', [ConfiguracaoController::class, 'limparCache'])->name('cache.limpar');

        Route::get('/moodle', [ConfiguracaoController::class, 'moodle'])->name('moodle');
        Route::post('/moodle/resync-all', [ConfiguracaoController::class, 'resyncAll'])->name('moodle.resyncAll');

        Route::get('/lti', [ConfiguracaoController::class, 'lti'])->name('lti');
        Route::post('/lti', [ConfiguracaoController::class, 'salvarLti'])->name('lti.salvar');
        Route::post('/lti/gerar-chaves', [ConfiguracaoController::class, 'gerarChavesLti'])->name('lti.gerar-chaves');
        Route::post('/lti/registro', [ConfiguracaoController::class, 'salvarRegistroLti'])->name('lti.registro.salvar');
        Route::delete('/lti/registro/{registration}', [ConfiguracaoController::class, 'excluirRegistroLti'])->name('lti.registro.excluir');
        Route::delete('/lti/resource-link/{resourceLink}', [ConfiguracaoController::class, 'excluirResourceLink'])->name('lti.resource-link.excluir');
    });

    // Manutenção (super_admin only)
    Route::post('/admin/manutencao/toggle', function () {
        abort_if(! auth()->user()->isSuperAdmin(), 403);
        if (MaintenanceMode::isActive()) {
            MaintenanceMode::disable();
            $msg = 'Modo de manutenção desativado. Sistema acessível para todos.';
        } else {
            MaintenanceMode::enable();
            $msg = 'Modo de manutenção ativado. Apenas administradores têm acesso.';
        }

        return back()->with('success', $msg);
    })->name('admin.manutencao.toggle')->middleware('role:super_admin');

    // Session health check (super_admin only, requires auth)
    Route::get('/diagnostico/sessao', function (\Illuminate\Http\Request $request) {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        return response()->json([
            'authenticated' => true,
            'user_id' => auth()->id(),
            'session_driver' => config('session.driver'),
            'session_id' => $request->session()->getId(),
            'is_secure' => $request->isSecure(),
            'url_scheme' => $request->getScheme(),
            'app_url' => config('app.url'),
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
            'session_same_site' => config('session.same_site'),
            'trusted_proxies' => 'configured',
            'server_https' => $request->server('HTTPS'),
            'forwarded_proto' => $request->header('X-Forwarded-Proto'),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'queue_driver' => config('queue.default'),
        ]);
    })->name('diagnostico.sessao');

    // Moodle sync diagnostic — shows full config chain for a session
    Route::get('/diagnostico/moodle/{session}', function (\App\Models\ExamSession $session) {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $session->loadMissing(['exam.clientSystem', 'student']);
        $exam = $session->exam;
        $system = $exam->clientSystem;
        $student = $session->student;

        $resolver = app(\App\Services\MoodleActivityResolver::class);
        $moodleConfig = $resolver->resolveForSession($session);

        $checks = [];
        $checks['student_has_moodle_user_id'] = ! empty($student?->moodle_user_id);
        $checks['student_moodle_user_id'] = $student?->moodle_user_id;
        $checks['student_cpf'] = $student?->cpf;
        $checks['system_has_moodle_integration'] = $system?->hasMoodleIntegration() ?? false;
        $checks['system_moodle_url'] = $system?->getMoodleUrl();
        $checks['system_has_token'] = ! empty($system?->moodle_config['token']);
        $checks['resolver_returned_config'] = $moodleConfig !== null;
        $checks['session_is_simulation'] = (bool) $session->is_simulation;
        $checks['session_was_lti'] = $session->wasLaunchedFromLti();
        $checks['session_status'] = $session->status;
        $checks['session_final_score'] = $session->final_score;
        $checks['session_moodle_synced'] = (bool) $session->moodle_synced;
        $checks['exam_settings'] = $exam->settings ?? [];

        if ($moodleConfig) {
            $checks['moodle_url'] = $moodleConfig['url'];
            $checks['moodle_course_id'] = $moodleConfig['course_id'];
            $checks['moodle_activity_id'] = $moodleConfig['activity_id'];
            $checks['moodle_component'] = $moodleConfig['component'];
            $checks['moodle_scale'] = $moodleConfig['scale'];
            $checks['course_id_valid'] = ($moodleConfig['course_id'] ?? 0) > 0;
            $checks['activity_id_valid'] = ($moodleConfig['activity_id'] ?? 0) > 0;
        }

        // Identify what would fail
        $blockers = [];
        if ($session->is_simulation) {
            $blockers[] = 'Sessão é simulação — sync ignorado';
        }
        if (! $system?->hasMoodleIntegration()) {
            $blockers[] = 'ClientSystem não tem integração Moodle configurada (url + token)';
        }
        if ($moodleConfig === null) {
            $blockers[] = 'MoodleActivityResolver retornou null — sem URL ou token';
        }
        if ($moodleConfig && ($moodleConfig['course_id'] ?? 0) <= 0) {
            $blockers[] = 'course_id não configurado (= 0)';
        }
        if ($moodleConfig && ($moodleConfig['activity_id'] ?? 0) <= 0) {
            $blockers[] = 'activity_id não configurado (= 0) e não foi possível resolver por nome';
        }
        if (empty($student?->moodle_user_id)) {
            $blockers[] = 'Estudante não tem moodle_user_id — Moodle não sabe para quem enviar a nota';
        }
        if ($session->final_score === null) {
            $blockers[] = 'Sessão não tem nota final (final_score é null)';
        }

        $checks['blockers'] = $blockers;
        $checks['would_sync'] = empty($blockers);

        // Check MoodleSyncLog for this session
        $syncLogs = \App\Models\MoodleSyncLog::where('session_id', $session->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'status', 'grade_sent', 'error_message', 'retry_count', 'synced_at', 'created_at'])
            ->toArray();
        $checks['sync_logs'] = $syncLogs;

        return response()->json($checks, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    })->name('diagnostico.moodle');

    // Manual re-sync — trigger Moodle grade sync for a session
    Route::post('/diagnostico/moodle/{session}/resync', function (\App\Models\ExamSession $session) {
        abort_if(! auth()->user()->isSuperAdmin(), 403);

        $session->loadMissing(['exam.clientSystem', 'student']);

        $service = app(\App\Services\MoodleSyncService::class);
        $result = $service->sync($session);

        return response()->json([
            'success' => $result,
            'session_id' => $session->id,
            'moodle_synced' => (bool) $session->fresh()->moodle_synced,
            'message' => $result ? 'Sync executado com sucesso' : 'Sync falhou — verifique os logs',
        ]);
    })->name('diagnostico.moodle.resync');

});

Route::get('/certificados/{code}/verificar', [CertificadoController::class, 'verify'])->name('certificados.verify');

Route::prefix('simulados')->name('simulados.public.')->group(function () {
    Route::get('/{slug}', [SimuladoPublicController::class, 'showInscricao'])->name('inscricao');
    Route::post('/{slug}/inscricao', [SimuladoPublicController::class, 'storeInscricao'])->name('inscricao.store');
});

// ── Diagnóstico público (não precisa de auth) ─────────────────────
// Acesso: /diagnostico/servidor?token=<APP_KEY base64>
Route::get('/diagnostico/servidor', function (\Illuminate\Http\Request $request) {
    $appKey = config('app.key');
    $expectedToken = substr(base64_encode($appKey), 0, 16);

    if ($request->query('token') !== $expectedToken) {
        abort(403, 'Token invalido.');
    }

    $sessionTable = config('session.table', 'sessions');
    $sessionTableExists = false;

    try {
        \Illuminate\Support\Facades\DB::select("SELECT 1 FROM {$sessionTable} LIMIT 1");
        $sessionTableExists = true;
    } catch (\Throwable) {
        $sessionTableExists = false;
    }

    $opcacheLoaded = function_exists('opcache_get_status');
    $opcacheStatus = $opcacheLoaded ? (opcache_get_status(false) ?: []) : [];

    return response()->json([
        'timestamp' => now()->toIso8601String(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'app_env' => config('app.env'),
        'app_url' => config('app.url'),
        'is_secure' => $request->isSecure(),
        'url_scheme' => $request->getScheme(),
        'server_https' => $request->server('HTTPS'),
        'forwarded_proto' => $request->header('X-Forwarded-Proto'),
        'forwarded_for' => $request->header('X-Forwarded-For'),
        'forwarded_host' => $request->header('X-Forwarded-Host'),
        'session' => [
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'domain' => config('session.domain'),
            'cookie_name' => config('session.cookie'),
            'http_only' => config('session.http_only'),
            'table_exists' => $sessionTableExists,
            'has_session_id' => $request->hasSession() && $request->session()->getId() !== '',
        ],
        'opcache' => [
            'loaded' => $opcacheLoaded,
            'enabled' => $opcacheStatus['opcache_enabled'] ?? false,
            'cached_scripts' => $opcacheStatus['opcache_statistics']['num_cached_scripts'] ?? 0,
        ],
        'trust_proxies' => 'configured_for_all',
        'authenticated' => auth()->check(),
        'auth_user_id' => auth()->id(),
    ]);
})->name('diagnostico.servidor');

// ── Fluxo do aluno (acesso via deep link JWT) ─────────────────────
Route::prefix('exam')->name('exam.')->group(function () {
    Route::get('/moodle/{exam}', [ExamWebController::class, 'moodleLaunch'])->name('moodle-launch');
    Route::post('/moodle/{exam}', [ExamWebController::class, 'startFromMoodle'])->name('moodle-launch.start');
    Route::get('/start', [ExamWebController::class, 'start'])->name('start');
    Route::post('/sessions/{id}/confirm-start', [ExamWebController::class, 'confirmStart'])->name('confirm-start');
    Route::get('/sessions/{id}', [ExamWebController::class, 'show'])->name('show');
    Route::post('/sessions/{id}/save-progress', [ExamWebController::class, 'saveProgress'])->name('save-progress');
    Route::post('/sessions/{id}/security-event', [ExamWebController::class, 'securityEvent'])->name('security-event');
    Route::post('/sessions/{id}/fingerprint', [ExamWebController::class, 'storeFingerprint'])->name('fingerprint');
    Route::post('/sessions/{id}/snapshot', [ExamWebController::class, 'storeSnapshot'])->name('snapshot');
    Route::post('/sessions/{id}/profile-photo', [ExamWebController::class, 'storeProfilePhoto'])->name('profile-photo');
    Route::post('/sessions/{id}/face-self-reference', [ExamWebController::class, 'storeFaceSelfReference'])->name('face-self-reference.store');
    Route::post('/sessions/{id}/face-verifications', [\App\Http\Controllers\Api\V1\FaceVerificationController::class, 'store'])->name('face-verifications.store');
    Route::get('/sessions/{id}/face-verifications', [\App\Http\Controllers\Api\V1\FaceVerificationController::class, 'index'])->name('face-verifications.index');
    Route::get('/sessions/{id}/enter-dashboard', [ExamWebController::class, 'enterDashboard'])->name('enter-dashboard');
    Route::post('/sessions/{id}/submit', [ExamWebController::class, 'submit'])->name('submit');
    Route::get('/sessions/{id}/result', [ExamWebController::class, 'result'])->name('result');
    Route::get('/sessions/{id}/terminated', [ExamWebController::class, 'terminated'])->name('terminated');
    Route::get('/error', fn () => view('exam.error', ['message' => session('message', 'Erro desconhecido.')]))->name('error');
});

// ── Monitoramento (professor) ─────────────────────────────────────
Route::prefix('monitor')->name('monitor.')->middleware('auth')->group(function () {
    Route::get('/', [OperacoesController::class, 'index'])
        ->middleware('role:super_admin,admin,professor')
        ->name('index');
    Route::get('/exams/{id}', [ExamWebController::class, 'monitor'])
        ->middleware('role:super_admin,admin,professor')
        ->name('exam');
});

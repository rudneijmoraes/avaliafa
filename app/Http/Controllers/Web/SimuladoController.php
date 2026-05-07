<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulados\StoreSimuladoRequest;
use App\Http\Requests\Simulados\UpdateSimuladoRequest;
use App\Jobs\BroadcastSimuladoEmails;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Simulado;
use App\Models\SimuladoEmailTemplate;
use App\Models\SimuladoHub;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Services\SimuladoEmailService;
use App\Services\SimuladoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TCPDF;

class SimuladoController extends Controller
{
    public function __construct(
        private readonly SimuladoService $simuladoService,
        private readonly SimuladoEmailService $emailService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Simulado::query()
            ->with(['exam', 'clientSystem', 'hub'])
            ->withCount('registrations')
            ->addSelect([
                'attempts_count' => ExamSession::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('exam_sessions.exam_id', 'simulados.exam_id')
                    ->where('exam_sessions.is_simulation', true),
            ]);

        if (! $user->isSuperAdmin()) {
            $query->where('client_system_id', $user->client_system_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('slug', 'like', $term));
        }

        $simulados = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('simulados.index', compact('simulados'));
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $systems = $user->isSuperAdmin()
                ? ClientSystem::query()->where('active', true)->orderBy('name')->get()
                : ClientSystem::query()->whereKey($user->client_system_id)->get();

            $templates = SimuladoEmailTemplate::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))
                ->where('active', true)
                ->orderBy('name')
                ->get();

            $html = view('simulados.form', [
                'simulado' => null,
                'systems' => $systems,
                'templates' => $templates,
                'availableHubs' => $this->availableHubs($user),
                'availableHubSlugs' => $this->availableHubSlugs($user),
                'simuladoSettingsSafe' => [],
                'examSettingsSafe' => [],
                'examDataSafe' => [],
                'availableExams' => $this->availableExamsForUser($user),
                'action' => route('simulados.store'),
                'method' => 'POST',
            ])->render();

            return response($html);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('simulados.index')
                ->with('error', 'Não foi possível abrir o formulário de simulado. Tente novamente.');
        }
    }

    public function store(StoreSimuladoRequest $request)
    {
        $data = $this->normalizeBooleanFlags($request, $request->validated());
        $notifyParticipants = $data['notify_participants_on_save'] ?? false;

        $simulado = $this->simuladoService->create($data, Auth::user());

        if ($notifyParticipants) {
            $sentCount = $this->emailService->sendNewSimuladoNotification($simulado);
            if ($sentCount > 0) {
                return redirect()->route('simulados.show', $simulado)
                    ->with('success', "Simulado criado com sucesso. E-mail enviado para {$sentCount} participante(s).");
            }
        }

        return redirect()->route('simulados.show', $simulado)->with('success', 'Simulado criado com sucesso.');
    }

    public function show(Simulado $simulado, Request $request)
    {
        $this->authorizeSimulado($simulado);
        $simulado->load(['exam', 'clientSystem', 'template']);

        $registrations = $simulado->registrations()
            ->with(['participant', 'examSession'])
            ->whereHas('participant')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->whereHas('participant', fn ($p) => $p
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('cpf', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $participants = $registrations->getCollection()
            ->pluck('participant')
            ->filter()
            ->unique('id')
            ->values();

        $participantIds = $participants->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $userIds = $participants->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $attemptsByUserId = [];
        if ($userIds !== []) {
            $attemptsByUserId = ExamSession::query()
                ->where('exam_id', $simulado->exam_id)
                ->whereIn('student_id', $userIds)
                ->selectRaw('student_id, COUNT(*) as total_attempts')
                ->groupBy('student_id')
                ->pluck('total_attempts', 'student_id')
                ->map(fn ($value) => (int) $value)
                ->all();
        }

        $fallbackAttemptsByParticipantId = [];
        if ($participantIds !== []) {
            $fallbackAttemptsByParticipantId = SimuladoRegistration::query()
                ->where('simulado_id', $simulado->id)
                ->whereIn('participant_id', $participantIds)
                ->selectRaw('participant_id, COUNT(*) as total_attempts')
                ->groupBy('participant_id')
                ->pluck('total_attempts', 'participant_id')
                ->map(fn ($value) => (int) $value)
                ->all();
        }

        $provasFeitasPorParticipante = [];
        foreach ($participants as $participant) {
            $participantId = (int) ($participant->id ?? 0);
            $userId = (int) ($participant->user_id ?? 0);

            if ($participantId <= 0) {
                continue;
            }

            $attempts = $userId > 0
                ? (int) ($attemptsByUserId[$userId] ?? 0)
                : (int) ($fallbackAttemptsByParticipantId[$participantId] ?? 0);

            $provasFeitasPorParticipante[$participantId] = max(1, $attempts);
        }

        $pendingEmailCount = $simulado->registrations()
            ->whereNull('email_sent_at')
            ->whereIn('status', ['registered', 'in_progress', 'completed'])
            ->count();

        $broadcastStudentCount = $this->emailService->countBroadcastStudents($simulado);

        return view('simulados.show', compact('simulado', 'registrations', 'provasFeitasPorParticipante', 'pendingEmailCount', 'broadcastStudentCount'));
    }

    public function notifyPending(Simulado $simulado)
    {
        $this->authorizeSimulado($simulado);
        $simulado->load('template');

        if (! $simulado->template || ! $simulado->template->active) {
            return redirect()->route('simulados.show', $simulado)
                ->with('warning', 'Nenhum template de e-mail ativo vinculado a este simulado.');
        }

        $count = $this->emailService->sendAvailabilityNotification($simulado, true);

        if ($count === 0) {
            return redirect()->route('simulados.show', $simulado)
                ->with('info', 'Nenhum participante pendente para notificar.');
        }

        return redirect()->route('simulados.show', $simulado)
            ->with('success', $count.' e-mail(s) enviado(s) com sucesso.');
    }

    public function broadcastAll(Simulado $simulado)
    {
        $this->authorizeSimulado($simulado);
        $simulado->load('template');

        if (! $simulado->template || ! $simulado->template->active) {
            return redirect()->route('simulados.show', $simulado)
                ->with('warning', 'Nenhum template de e-mail ativo vinculado a este simulado.');
        }

        $total = $this->emailService->countBroadcastStudents($simulado);

        if ($total === 0) {
            return redirect()->route('simulados.show', $simulado)
                ->with('info', 'Nenhum aluno com e-mail cadastrado encontrado.');
        }

        // Registra o total alvo imediatamente; broadcast_total_sent será atualizado pelo job
        $simulado->update([
            'broadcast_sent_at'      => null,
            'broadcast_total_sent'   => 0,
            'broadcast_total_target' => $total,
        ]);

        BroadcastSimuladoEmails::dispatch($simulado->id);

        return redirect()->route('simulados.show', $simulado)
            ->with('success', 'Envio agendado para '.$total.' aluno(s). Os e-mails serão disparados em segundo plano em lotes de 50. Atualize a página após alguns minutos para ver o resultado.');
    }

    public function notifyOne(Simulado $simulado, SimuladoParticipant $participant)
    {
        $this->authorizeSimulado($simulado);
        $simulado->load('template');

        if (! $simulado->template || ! $simulado->template->active) {
            return redirect()->route('simulados.show', $simulado)
                ->with('warning', 'Nenhum template de e-mail ativo vinculado a este simulado.');
        }

        if (! $participant->email) {
            return redirect()->route('simulados.show', $simulado)
                ->with('warning', 'Participante não possui e-mail cadastrado.');
        }

        $registration = $simulado->registrations()
            ->where('participant_id', $participant->id)
            ->latest('id')
            ->first();

        if (! $registration) {
            return redirect()->route('simulados.show', $simulado)
                ->with('warning', 'Inscrição não encontrada para este participante.');
        }

        $sent = $this->emailService->sendToParticipant($simulado, $participant, $registration);

        if ($sent) {
            return redirect()->route('simulados.show', $simulado)
                ->with('success', 'E-mail enviado para '.$participant->email.'.');
        }

        return redirect()->route('simulados.show', $simulado)
            ->with('warning', 'Falha ao enviar. Verifique as configurações de e-mail do servidor.');
    }

    public function edit(Simulado $simulado)
    {
        try {
            $user = Auth::user();
            $this->authorizeSimulado($simulado);

            $systems = $user->isSuperAdmin()
                ? ClientSystem::query()->where('active', true)->orderBy('name')->get()
                : ClientSystem::query()->whereKey($user->client_system_id)->get();

            $templates = SimuladoEmailTemplate::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))
                ->where('active', true)
                ->orderBy('name')
                ->get();

            $simuladoSettingsSafe = [];
            $rawSimulado = DB::table('simulados')->select(['settings', 'name', 'slug', 'description', 'status', 'template_id', 'capture_photo_enabled', 'webcam_enabled', 'fullscreen_enabled', 'show_result_immediately', 'auto_email_enabled', 'moodle_integration_enabled', 'exam_id'])->where('id', $simulado->id)->first();
            if ($rawSimulado) {
                if (is_string($rawSimulado->settings) && $rawSimulado->settings !== '') {
                    $decoded = json_decode($rawSimulado->settings, true);
                    $simuladoSettingsSafe = is_array($decoded) ? $decoded : [];
                }
            }

            $examSettingsSafe = [];
            $examDataSafe = [];
            if ($rawSimulado && $rawSimulado->exam_id) {
                $rawExam = DB::table('exams')
                    ->select([
                        'duration_minutes',
                        'passing_score',
                        'max_violations',
                        'shuffle_questions',
                        'shuffle_choices',
                        'starts_at',
                        'ends_at',
                        'settings',
                    ])
                    ->where('id', $rawSimulado->exam_id)
                    ->first();

                if ($rawExam) {
                    $examDataSafe = [
                        'duration_minutes' => $rawExam->duration_minutes,
                        'passing_score' => $rawExam->passing_score,
                        'max_violations' => $rawExam->max_violations,
                        'shuffle_questions' => (bool) $rawExam->shuffle_questions,
                        'shuffle_choices' => (bool) $rawExam->shuffle_choices,
                        'starts_at' => $rawExam->starts_at,
                        'ends_at' => $rawExam->ends_at,
                    ];

                    if (is_string($rawExam->settings) && $rawExam->settings !== '') {
                        $decoded = json_decode($rawExam->settings, true);
                        $examSettingsSafe = is_array($decoded) ? $decoded : [];
                    }
                }
            }

            $availableHubSlugs = [];
            try {
                $availableHubSlugs = $this->availableHubSlugs($user);
            } catch (\Throwable $e) {
                report($e);
            }

            $simulado->name = $rawSimulado->name ?? $simulado->name;
            $simulado->slug = $rawSimulado->slug ?? $simulado->slug;
            $simulado->description = $rawSimulado->description ?? $simulado->description;
            $simulado->status = $rawSimulado->status ?? $simulado->status;
            $simulado->template_id = $rawSimulado->template_id ?? $simulado->template_id;
            $simulado->capture_photo_enabled = $rawSimulado->capture_photo_enabled ?? false;
            $simulado->webcam_enabled = $rawSimulado->webcam_enabled ?? true;
            $simulado->fullscreen_enabled = $rawSimulado->fullscreen_enabled ?? true;
            $simulado->show_result_immediately = $rawSimulado->show_result_immediately ?? true;
            $simulado->auto_email_enabled = $rawSimulado->auto_email_enabled ?? true;
            $simulado->moodle_integration_enabled = $rawSimulado->moodle_integration_enabled ?? false;
            $simulado->hub_id = $simulado->hub_id;

            $html = view('simulados.form', [
                'simulado' => $simulado,
                'systems' => $systems,
                'templates' => $templates,
                'availableHubs' => $this->availableHubs($user),
                'availableHubSlugs' => $availableHubSlugs,
                'simuladoSettingsSafe' => $simuladoSettingsSafe,
                'examSettingsSafe' => $examSettingsSafe,
                'examDataSafe' => $examDataSafe,
                'availableExams' => collect(),
                'action' => route('simulados.update', $simulado),
                'method' => 'PUT',
            ])->render();

            return response($html);
        } catch (\Throwable $e) {
            report($e);
            \Log::error('Erro no edit simulado ID '.$simulado->id.': '.$e->getMessage().' | Arquivo: '.$e->getFile().':'.$e->getLine());

            return redirect()->route('simulados.index')
                ->with('error', 'Não foi possível abrir a edição. Erro: '.$e->getMessage());
        }
    }

    public function update(UpdateSimuladoRequest $request, Simulado $simulado)
    {
        $this->authorizeSimulado($simulado);

        try {
            $data = $this->normalizeBooleanFlags($request, $request->validated());
            $notifyParticipants = $data['notify_participants_on_save'] ?? false;

            $this->simuladoService->update($simulado, $data);

            if ($notifyParticipants) {
                $sentCount = $this->emailService->sendNewSimuladoNotification($simulado);
                if ($sentCount > 0) {
                    return redirect()->route('simulados.show', $simulado)
                        ->with('success', "Simulado atualizado com sucesso. E-mail enviado para {$sentCount} participante(s).");
                }
            }

            return redirect()->route('simulados.show', $simulado)->with('success', 'Simulado atualizado com sucesso.');
        } catch (\Throwable $e) {
            report($e);
            \Log::error('Erro ao salvar simulado ID '.$simulado->id.': '.$e->getMessage().' | Arquivo: '.$e->getFile().':'.$e->getLine());

            return back()
                ->withInput()
                ->with('error', 'Não foi possível salvar a edição do simulado. Verifique os dados e tente novamente.');
        }
    }

    public function destroy(Simulado $simulado)
    {
        $this->authorizeSimulado($simulado);

        $name = $simulado->name;
        $simulado->delete();
        $simulado->exam()?->update(['status' => 'archived']);

        return redirect()->route('simulados.index')->with('success', 'Simulado "'.$name.'" excluido com sucesso.');
    }

    public function duplicate(Simulado $simulado)
    {
        $this->authorizeSimulado($simulado);
        $simulado->loadMissing(['exam.examQuestions', 'template']);
        abort_unless($simulado->exam, 422, 'Não foi possível duplicar: exame não encontrado.');

        $newSimulado = DB::transaction(function () use ($simulado) {
            $sourceExam = $simulado->exam;
            $baseName = $this->nextCopyName((string) $simulado->name);

            $newExam = \App\Models\Exam::query()->create([
                'client_system_id' => $sourceExam->client_system_id,
                'discipline_id' => $sourceExam->discipline_id,
                'created_by' => Auth::id(),
                'title' => $baseName,
                'description' => $sourceExam->description,
                'status' => 'draft',
                'duration_minutes' => (int) $sourceExam->duration_minutes,
                'max_violations' => (int) $sourceExam->max_violations,
                'webcam_enabled' => (bool) $sourceExam->webcam_enabled,
                'shuffle_questions' => (bool) $sourceExam->shuffle_questions,
                'shuffle_choices' => (bool) $sourceExam->shuffle_choices,
                'passing_score' => (float) $sourceExam->passing_score,
                'settings' => $sourceExam->settings ?? [],
                'starts_at' => $sourceExam->starts_at,
                'ends_at' => $sourceExam->ends_at,
            ]);

            foreach ($sourceExam->examQuestions as $examQuestion) {
                $newExam->examQuestions()->create([
                    'question_id' => $examQuestion->question_id,
                    'order' => $examQuestion->order,
                    'weight' => $examQuestion->weight,
                ]);
            }

            $newSettings = $simulado->settings ?? [];
            if (empty(data_get($newSettings, 'weekly_label'))) {
                data_set($newSettings, 'weekly_label', 'Nova semana');
            }

            $hubAvailable = Schema::hasColumn('simulados', 'hub_id') && Schema::hasColumn('simulados', 'hub_order');
            $nextHubOrder = ($hubAvailable && $simulado->hub_id)
                ? ((int) Simulado::query()->where('hub_id', $simulado->hub_id)->max('hub_order')) + 1
                : null;

            return Simulado::query()->create(array_filter([
                'exam_id' => $newExam->id,
                'client_system_id' => $simulado->client_system_id,
                'created_by' => Auth::id(),
                'template_id' => $simulado->template_id,
                'hub_id' => $hubAvailable ? $simulado->hub_id : null,
                'hub_order' => $nextHubOrder,
                'slug' => $this->nextCopySlug((string) $simulado->slug),
                'name' => $baseName,
                'description' => $simulado->description,
                'status' => 'draft',
                'capture_photo_enabled' => (bool) $simulado->capture_photo_enabled,
                'webcam_enabled' => (bool) $simulado->webcam_enabled,
                'fullscreen_enabled' => (bool) $simulado->fullscreen_enabled,
                'show_result_immediately' => (bool) $simulado->show_result_immediately,
                'auto_email_enabled' => (bool) $simulado->auto_email_enabled,
                'moodle_integration_enabled' => (bool) $simulado->moodle_integration_enabled,
                'settings' => $newSettings,
            ], fn ($v) => ! is_null($v)));
        });

        return redirect()
            ->route('simulados.edit', $newSimulado)
            ->with('success', 'Simulado duplicado com sucesso. Revise e publique a nova semana.');
    }

    public function editParticipant(Simulado $simulado, SimuladoParticipant $participant)
    {
        $this->authorizeSimulado($simulado);
        abort_unless($participant->client_system_id === $simulado->client_system_id, 404);

        return view('simulados.participants.form', compact('simulado', 'participant'));
    }

    public function updateParticipant(Request $request, Simulado $simulado, SimuladoParticipant $participant)
    {
        $this->authorizeSimulado($simulado);
        abort_unless($participant->client_system_id === $simulado->client_system_id, 404);

        $data = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|min:10|max:20',
            'cpf' => 'required|string|min:11|max:14',
        ]);

        $participant->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => preg_replace('/\D/', '', (string) $data['phone']),
            'cpf' => preg_replace('/\D/', '', (string) $data['cpf']),
        ]);

        if ($participant->user_id) {
            $participant->user()->update([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
            ]);
        }

        return redirect()->route('simulados.show', $simulado)->with('success', 'Cadastro do participante atualizado com sucesso.');
    }

    public function destroyParticipant(Simulado $simulado, SimuladoParticipant $participant)
    {
        $this->authorizeSimulado($simulado);
        abort_unless($participant->client_system_id === $simulado->client_system_id, 404);

        $participantName = $participant->full_name !== '' ? $participant->full_name : 'Participante';
        $participant->delete();

        return redirect()->route('simulados.show', $simulado)->with('success', $participantName.' removido(a) com sucesso do simulado.');
    }

    public function toggleStatus(Simulado $simulado)
    {
        $this->authorizeSimulado($simulado);

        $newStatus = $simulado->status === 'active' ? 'inactive' : 'active';
        $simulado->update(['status' => $newStatus]);
        $simulado->exam()->update(['status' => $newStatus === 'active' ? 'active' : 'draft']);

        return back()->with('success', 'Status atualizado.');
    }

    public function exportExcel(Simulado $simulado): StreamedResponse
    {
        $this->authorizeSimulado($simulado);
        $rows = $this->baseReportRows($simulado);

        $xmlHeader = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
        $xmlHeader .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xmlHeader .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Simulado">';
        $xmlHeader .= '<Table>';
        $xmlHeader .= '<Row><Cell><Data ss:Type="String">Sessao</Data></Cell><Cell><Data ss:Type="String">Tentativa</Data></Cell><Cell><Data ss:Type="String">Nome</Data></Cell><Cell><Data ss:Type="String">CPF</Data></Cell><Cell><Data ss:Type="String">E-mail</Data></Cell><Cell><Data ss:Type="String">Telefone</Data></Cell><Cell><Data ss:Type="String">Status</Data></Cell><Cell><Data ss:Type="String">Nota</Data></Cell><Cell><Data ss:Type="String">% Acertos</Data></Cell><Cell><Data ss:Type="String">Acertos</Data></Cell><Cell><Data ss:Type="String">Erros</Data></Cell><Cell><Data ss:Type="String">Inscricao</Data></Cell><Cell><Data ss:Type="String">Conclusao</Data></Cell></Row>';

        $xmlBody = '';
        foreach ($rows as $row) {
            $xmlBody .= '<Row>';
            foreach ($row as $value) {
                $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xmlBody .= '<Cell><Data ss:Type="String">'.$escaped.'</Data></Cell>';
            }
            $xmlBody .= '</Row>';
        }

        $xmlFooter = '</Table></Worksheet></Workbook>';
        $xml = $xmlHeader.$xmlBody.$xmlFooter;

        return response()->streamDownload(
            fn () => print ($xml),
            'simulado-'.$simulado->id.'-inscritos.xml',
            ['Content-Type' => 'application/vnd.ms-excel']
        );
    }

    public function exportPdf(Simulado $simulado): Response
    {
        $this->authorizeSimulado($simulado);
        $rows = $this->baseReportRows($simulado);

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AvaliaFA');
        $pdf->SetAuthor('AvaliaFA');
        $pdf->SetTitle('Relatorio de Simulado');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->AddPage();

        $logoPath = public_path('storage/logo-deitada-transparente.png');
        if ($this->canRenderTcpdfLogo($logoPath)) {
            $pdf->Image($logoPath, 8, 8, 56, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $pdf->Ln(15);
        }

        $participantsCount = count($rows);
        $generatedAt = now()->format('d/m/Y H:i');

        $html = '<div style="background:#F8FAFF;border:1px solid #BFDBFE;border-radius:12px;padding:14px 16px;margin-bottom:8px">';
        $html .= '<div style="font-size:17px;font-weight:bold;color:#1D4ED8;margin-bottom:4px">Relatorio de Simulado - '.e($simulado->name).'</div>';
        $html .= '<div style="font-size:10.5px;color:#334155">Dados completos dos participantes para acompanhamento e acoes de relacionamento.</div>';
        $html .= '<table cellspacing="0" cellpadding="4" border="0" style="margin-top:8px;font-size:10px;color:#1E293B">';
        $html .= '<tr>';
        $html .= '<td style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px"><b>Total de registros:</b> '.e((string) $participantsCount).'</td>';
        $html .= '<td style="width:8px"></td>';
        $html .= '<td style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px"><b>Gerado em:</b> '.e($generatedAt).'</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<table cellspacing="0" cellpadding="5" border="0.5" style="border-color:#CBD5E1;font-size:8.3px;line-height:1.35;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color:#1D4ED8;color:#FFFFFF;font-weight:bold;text-align:center">';
        $html .= '<th width="6%">Sessao</th>';
        $html .= '<th width="5%">Tent.</th>';
        $html .= '<th width="12%">Nome</th>';
        $html .= '<th width="8%">CPF</th>';
        $html .= '<th width="14%">E-mail</th>';
        $html .= '<th width="8%">Telefone</th>';
        $html .= '<th width="10%">Status</th>';
        $html .= '<th width="6%">Nota</th>';
        $html .= '<th width="6%">%</th>';
        $html .= '<th width="5%">Acertos</th>';
        $html .= '<th width="5%">Erros</th>';
        $html .= '<th width="7.5%">Inscricao</th>';
        $html .= '<th width="7.5%">Conclusao</th>';
        $html .= '</tr>';
        $html .= '</thead><tbody>';
        foreach ($rows as $index => $row) {
            $rowBackground = $index % 2 === 0 ? '#FFFFFF' : '#F8FAFC';
            $html .= '<tr style="background-color:'.$rowBackground.';color:#0F172A">';
            $html .= '<td align="center">'.e($row[0]).'</td>';
            $html .= '<td align="center">'.e($row[1]).'</td>';
            $html .= '<td>'.e($row[2]).'</td>';
            $html .= '<td>'.e($row[3]).'</td>';
            $html .= '<td style="word-break:break-all">'.e($row[4]).'</td>';
            $html .= '<td>'.e($row[5]).'</td>';
            $html .= '<td>'.e($row[6]).'</td>';
            $html .= '<td align="center">'.e($row[7]).'</td>';
            $html .= '<td align="center">'.e($row[8]).'</td>';
            $html .= '<td align="center">'.e($row[9]).'</td>';
            $html .= '<td align="center">'.e($row[10]).'</td>';
            $html .= '<td align="center">'.e($row[11]).'</td>';
            $html .= '<td align="center">'.e($row[12]).'</td>';
            $html .= '</tr>';
        }
        if ($rows === []) {
            $html .= '<tr><td colspan="13" align="center" style="padding:16px;color:#64748B">Nenhum participante encontrado para este simulado.</td></tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return response(
            $pdf->Output('', 'S'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="simulado-'.$simulado->id.'-inscritos.pdf"',
            ]
        );
    }

    public function exportParticipantPdf(Simulado $simulado, SimuladoParticipant $participant): Response
    {
        $this->authorizeSimulado($simulado);
        abort_unless($participant->client_system_id === $simulado->client_system_id, 404);

        $participant->loadMissing('user', 'clientSystem');

        $fullName = $participant->full_name !== '' ? $participant->full_name : 'Participante';
        $email = $participant->email ?: ($participant->user?->email ?: 'Nao informado');
        $cpf = $participant->cpf ?: ($participant->user?->cpf ?: 'Nao informado');
        $phone = $participant->phone ?: 'Nao informado';
        $clientSystemName = $participant->clientSystem?->name ?: 'Sistema AvaliaFA';
        $completedSimulados = $this->countParticipantCompletedSimulados((int) $participant->id);
        $totalRegistrations = (int) SimuladoRegistration::query()
            ->where('participant_id', $participant->id)
            ->count();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AvaliaFA');
        $pdf->SetAuthor('AvaliaFA');
        $pdf->SetTitle('Perfil Personalizado - '.$fullName);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $logoPath = public_path('storage/logo-deitada-transparente.png');
        if ($this->canRenderTcpdfLogo($logoPath)) {
            $pdf->Image($logoPath, 12, 10, 62, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $pdf->Ln(18);
        }

        $issuedAt = now()->format('d/m/Y H:i');

        $html = '<div style="background:#F8FAFF;border:1px solid #BFDBFE;border-radius:12px;padding:14px 16px;margin-bottom:9px">';
        $html .= '<div style="font-size:17px;font-weight:bold;color:#1D4ED8;margin-bottom:4px">Perfil Personalizado do Participante</div>';
        $html .= '<div style="font-size:10.5px;color:#334155">Documento visual para relacionamento e campanhas de marketing.</div>';
        $html .= '<table cellspacing="0" cellpadding="4" border="0" style="margin-top:8px;font-size:10px;color:#1E293B">';
        $html .= '<tr>';
        $html .= '<td style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px"><b>Sistema:</b> '.e($clientSystemName).'</td>';
        $html .= '<td style="width:8px"></td>';
        $html .= '<td style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px"><b>Emitido em:</b> '.e($issuedAt).'</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<table cellspacing="0" cellpadding="6" border="0.6" style="border-color:#CBD5E1;font-size:10px;line-height:1.45;">';
        $html .= '<thead><tr style="background-color:#1D4ED8;color:#FFFFFF;font-weight:bold;text-align:center">';
        $html .= '<th width="36%">Informacao</th>';
        $html .= '<th width="64%">Valor</th>';
        $html .= '</tr></thead><tbody>';
        $html .= '<tr style="background-color:#FFFFFF"><td><b>Nome completo</b></td><td>'.e($fullName).'</td></tr>';
        $html .= '<tr style="background-color:#F8FAFC"><td><b>E-mail</b></td><td style="word-break:break-all">'.e($email).'</td></tr>';
        $html .= '<tr style="background-color:#FFFFFF"><td><b>CPF</b></td><td>'.e($cpf).'</td></tr>';
        $html .= '<tr style="background-color:#F8FAFC"><td><b>Telefone</b></td><td>'.e($phone).'</td></tr>';
        $html .= '<tr style="background-color:#FFFFFF"><td><b>Total de simulados realizados</b></td><td><b>'.e((string) $completedSimulados).'</b></td></tr>';
        $html .= '<tr style="background-color:#F8FAFC"><td><b>Total de inscricoes no modulo</b></td><td>'.e((string) $totalRegistrations).'</td></tr>';
        $html .= '<tr style="background-color:#FFFFFF"><td><b>Simulado de referencia</b></td><td>'.e($simulado->name).'</td></tr>';
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'perfil-participante-'.Str::slug($fullName !== '' ? $fullName : 'simulado').'-v1.pdf';

        return response(
            $pdf->Output('', 'S'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    private function baseReportRows(Simulado $simulado): array
    {
        return $simulado->registrations()
            ->with(['participant', 'examSession'])
            ->whereHas('participant')
            ->orderByDesc('id')
            ->get()
            ->map(function ($registration) {
                $participant = $registration->participant;
                $session = $registration->examSession;

                if (! $participant) {
                    return null;
                }

                return [
                    $session?->id ? '#'.$session->id : '-',
                    $session?->attempt_number ? $session->attempt_number.'a' : '-',
                    trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                    $participant->cpf ?? '',
                    $participant->email ?? '',
                    $participant->phone ?? '',
                    $this->registrationStatusLabel($registration->status),
                    number_format((float) ($registration->final_score ?? 0), 2, ',', '.'),
                    number_format((float) ($registration->percentage_correct ?? 0), 2, ',', '.'),
                    (string) ($registration->total_correct ?? 0),
                    (string) ($registration->total_wrong ?? 0),
                    optional($registration->registered_at)->format('d/m/Y H:i'),
                    optional($registration->completed_at)->format('d/m/Y H:i'),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function authorizeSimulado(Simulado $simulado): void
    {
        $user = Auth::user();
        abort_unless($user->isProfessor(), 403);
        if (! $user->isSuperAdmin() && $simulado->client_system_id !== $user->client_system_id) {
            abort(403);
        }
    }

    private function normalizeBooleanFlags(Request $request, array $data): array
    {
        foreach ([
            'capture_photo_enabled',
            'webcam_enabled',
            'fullscreen_enabled',
            'show_result_immediately',
            'auto_email_enabled',
            'moodle_integration_enabled',
            'shuffle_questions',
            'shuffle_choices',
            'notify_participants_on_save',
        ] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }

    private function countParticipantCompletedSimulados(int $participantId): int
    {
        return (int) SimuladoRegistration::query()
            ->where('participant_id', $participantId)
            ->where(function ($query) {
                $query->whereNotNull('completed_at')
                    ->orWhereIn('status', ['completed', 'email_sent']);
            })
            ->count();
    }

    private function canRenderTcpdfLogo(string $logoPath): bool
    {
        if (! is_file($logoPath)) {
            return false;
        }

        if (! defined('K_PATH_CACHE')) {
            return true;
        }

        $cachePath = (string) K_PATH_CACHE;

        return $cachePath !== '' && is_dir($cachePath) && is_writable($cachePath);
    }

    private function registrationStatusLabel(string $status): string
    {
        return [
            'registered' => 'Inscrito',
            'in_progress' => 'Em andamento',
            'completed' => 'Concluido',
            'email_sent' => 'E-mail enviado',
            'cancelled' => 'Cancelado',
        ][$status] ?? $status;
    }

    private function nextCopyName(string $name): string
    {
        $base = trim($name).' - Cópia';
        $candidate = $base;
        $counter = 2;

        while (Simulado::query()->where('name', $candidate)->exists()) {
            $candidate = $base.' '.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function nextCopySlug(string $slug): string
    {
        $base = Str::slug($slug.'-copia');
        $candidate = $base !== '' ? $base : 'simulado-copia';
        $counter = 2;

        while (Simulado::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function availableExamsForUser($user): \Illuminate\Support\Collection
    {
        return Exam::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))
            ->whereNotIn('status', ['archived'])
            ->withCount('questions')
            ->orderByDesc('id')
            ->get(['id', 'title', 'status', 'client_system_id']);
    }

    private function availableHubSlugs($user): array
    {
        try {
            $hubSlugs = SimuladoHub::query()
                ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('client_system_id', $user->client_system_id))
                ->pluck('slug')
                ->filter(fn ($value) => filled($value))
                ->values()
                ->all();

            $rows = DB::table('simulados')
                ->select(['slug', 'settings', 'client_system_id'])
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))
                ->orderByDesc('id')
                ->limit(300)
                ->get();

            return collect($hubSlugs)
                ->merge(collect($rows)->map(function ($row) {
                    $settings = [];
                    if (is_string($row->settings) && $row->settings !== '') {
                        $decoded = json_decode($row->settings, true);
                        $settings = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($row->settings)) {
                        $settings = $row->settings;
                    }

                    $hub = trim((string) data_get($settings, 'public_hub_slug', ''));
                    $slug = trim((string) ($row->slug ?? ''));

                    return $hub !== '' ? $hub : $slug;
                }))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private function availableHubs($user)
    {
        if (! $this->hubFeaturesAvailable()) {
            return collect();
        }

        return SimuladoHub::query()
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('client_system_id', $user->client_system_id))
            ->with('clientSystem:id,name')
            ->orderBy('name')
            ->get(['id', 'client_system_id', 'name', 'slug', 'landing_title', 'status']);
    }

    private function hubFeaturesAvailable(): bool
    {
        try {
            return Schema::hasTable('simulado_hubs')
                && Schema::hasColumn('simulados', 'hub_id')
                && Schema::hasColumn('simulados', 'hub_order');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

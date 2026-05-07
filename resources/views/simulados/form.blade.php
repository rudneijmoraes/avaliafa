@extends('layouts.app')

@section('title', ($simulado ? 'Editar Simulado' : 'Novo Simulado').' — AvaliaFA')
@section('page-title', $simulado ? 'Editar Simulado' : 'Novo Simulado')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $simulado ? 'Editar Simulado' : 'Novo Simulado' }}</h1>
        <p class="page-subtitle">Configuração administrativa, regras de aplicação e integração opcional.</p>
    </div>
</div>

<div class="card" style="padding:20px">
    @if($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#991B1B;font-size:0.875rem">
        <strong>Corrija os erros antes de salvar:</strong>
        <ul style="margin:6px 0 0 16px;padding:0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form method="POST" action="{{ $action }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif
        @php
            $simuladoModel = $simulado;
            $simuladoSettings = is_array($simuladoSettingsSafe ?? null) ? $simuladoSettingsSafe : [];
            $examSettings = is_array($examSettingsSafe ?? null) ? $examSettingsSafe : [];
            $examData = is_array($examDataSafe ?? null) ? $examDataSafe : [];
            $availableHubs = $availableHubs ?? collect();
            $hubSlugs = is_array($availableHubSlugs ?? null) ? $availableHubSlugs : [];

            $simuladoName = old('name', data_get($simuladoModel, 'name', ''));
            $simuladoDescription = old('description', data_get($simuladoModel, 'description', ''));
            $introTextValue = old('intro_text', data_get($simuladoSettings, 'intro_text', data_get($examSettings, 'simulado.intro_text', '')));
            $layoutMode = old('layout_mode', data_get($simuladoSettings, 'layout_mode', data_get($examSettings, 'simulado.layout_mode', 'step')));
            $selectedClientSystemId = (string) old('client_system_id', data_get($simuladoModel, 'client_system_id', ''));
            $selectedStatus = old('status', data_get($simuladoModel, 'status', 'draft'));
            $durationMinutes = old('duration_minutes', data_get($examData, 'duration_minutes', 60));
            $passingScore = old('passing_score', data_get($examData, 'passing_score', 6));
            $maxViolations = old('max_violations', data_get($examData, 'max_violations', 3));
            $maxAttempts = old('max_attempts', data_get($simuladoSettings, 'max_attempts', 0));
            $startsAtValue = old('starts_at', filled(data_get($examData, 'starts_at')) ? \Illuminate\Support\Carbon::parse(data_get($examData, 'starts_at'))->format('Y-m-d\TH:i') : '');
            $endsAtValue = old('ends_at', filled(data_get($examData, 'ends_at')) ? \Illuminate\Support\Carbon::parse(data_get($examData, 'ends_at'))->format('Y-m-d\TH:i') : '');
            $selectedTemplateId = (string) old('template_id', data_get($simuladoModel, 'template_id', ''));
            $moodleCourseId = old('moodle_course_id', data_get($simuladoSettings, 'moodle.course_id', 0));
            $moodleActivityId = old('moodle_activity_id', data_get($simuladoSettings, 'moodle.activity_id', 0));
            $moodleActivityName = old('moodle_activity_name', data_get($simuladoSettings, 'moodle.activity_name', ''));
            $selectedHubId = (string) old('hub_id', data_get($simuladoModel, 'hub_id', ''));
            $publicHubSlug = old('public_hub_slug', data_get($simuladoSettings, 'public_hub_slug', data_get($simuladoModel, 'slug', '')));
            $weeklyLabel = old('weekly_label', data_get($simuladoSettings, 'weekly_label', ''));
            $brandingWatermarkText = old('branding_watermark_text', data_get($examSettings, 'branding_watermark_text', data_get($simuladoSettings, 'branding_watermark_text', '')));
            $flagValueSources = [
                'capture_photo_enabled' => data_get($simuladoModel, 'capture_photo_enabled'),
                'webcam_enabled' => data_get($simuladoModel, 'webcam_enabled'),
                'fullscreen_enabled' => data_get($simuladoModel, 'fullscreen_enabled'),
                'show_result_immediately' => data_get($simuladoModel, 'show_result_immediately'),
                'auto_email_enabled' => data_get($simuladoModel, 'auto_email_enabled'),
                'moodle_integration_enabled' => data_get($simuladoModel, 'moodle_integration_enabled'),
                'shuffle_questions' => data_get($examData, 'shuffle_questions'),
                'shuffle_choices' => data_get($examData, 'shuffle_choices'),
                'notify_participants_on_save' => data_get($simuladoSettings, 'notify_participants_on_save', false),
            ];
        @endphp

        <div style="grid-column:1/-1">
            <label class="form-label">Nome do simulado</label>
            <input class="input" name="name" value="{{ $simuladoName }}" required>
        </div>

        <div style="grid-column:1/-1">
            <label class="form-label">Descrição</label>
            <textarea class="input" name="description" rows="3">{{ $simuladoDescription }}</textarea>
        </div>
        @if(! $simulado && ($availableExams ?? collect())->isNotEmpty())
        <div style="grid-column:1/-1" x-data="{ examMode: '{{ old('existing_exam_id') ? 'existing' : 'new' }}' }">
            <div style="border:1px solid var(--surface-border);border-radius:12px;padding:16px 20px;background:var(--surface-bg)">
                <div style="font-size:0.88rem;font-weight:700;color:var(--text-primary);margin-bottom:12px">Banco de questões (prova)</div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 16px;border-radius:8px;border:1px solid var(--surface-border);background:white" :style="examMode==='new' ? 'border-color:var(--color-primary-600);background:#EFF6FF' : ''">
                        <input type="radio" x-model="examMode" value="new" style="accent-color:var(--color-primary-600)">
                        <span style="font-size:0.85rem">
                            <strong>Criar nova prova</strong><br>
                            <span style="color:var(--text-muted);font-size:0.78rem">Prova em branco — adicione questões depois</span>
                        </span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 16px;border-radius:8px;border:1px solid var(--surface-border);background:white" :style="examMode==='existing' ? 'border-color:var(--color-primary-600);background:#EFF6FF' : ''">
                        <input type="radio" x-model="examMode" value="existing" style="accent-color:var(--color-primary-600)">
                        <span style="font-size:0.85rem">
                            <strong>Usar prova existente</strong><br>
                            <span style="color:var(--text-muted);font-size:0.78rem">Reaproveite questões já cadastradas</span>
                        </span>
                    </label>
                </div>
                <div x-show="examMode==='existing'" x-cloak>
                    <label class="form-label">Selecionar prova existente</label>
                    <select class="input" name="existing_exam_id">
                        <option value="">— selecione —</option>
                        @foreach($availableExams as $availExam)
                        <option value="{{ $availExam->id }}" {{ (string) old('existing_exam_id') === (string) $availExam->id ? 'selected' : '' }}>
                            #{{ $availExam->id }} — {{ $availExam->title }}
                            ({{ $availExam->questions_count }} questão(ões) · {{ match($availExam->status) { 'active' => 'Ativa', 'draft' => 'Rascunho', default => ucfirst($availExam->status) } }})
                        </option>
                        @endforeach
                    </select>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:6px">
                        Os campos de duração, nota de corte e violações abaixo serão ignorados ao usar prova existente.
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div style="grid-column:1/-1">
            <label class="form-label">Texto introdutório base (opcional)</label>
            <textarea class="input" name="intro_text" rows="8" placeholder="Cole aqui o texto-base grande para leitura antes das questões. Para usar 2 textos, separe com uma linha contendo ---">{{ $introTextValue }}</textarea>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Esse conteúdo aparece no topo da prova para simulados. Se precisar de dois blocos, separe usando uma linha com três hífens: ---
            </div>
        </div>
        <div>
            <label class="form-label">Modo de exibição das questões</label>
            <select class="input" name="layout_mode">
                <option value="step" @selected($layoutMode === 'step')>Por etapas (uma questão por vez)</option>
                <option value="single_page" @selected($layoutMode === 'single_page')>Página única (todas as questões)</option>
            </select>
        </div>
        <div>
            <label class="form-label">Marca institucional no topo</label>
            <input class="input" name="branding_watermark_text" value="{{ $brandingWatermarkText }}" placeholder="ex: Anasps">
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Aparece centralizada e discreta no topo das questões, orientações e textos-base.
            </div>
        </div>

        @if(auth()->user()->isSuperAdmin())
        <div>
            <label class="form-label">Sistema</label>
            <select class="input" name="client_system_id" required>
                @foreach($systems as $system)
                <option value="{{ $system->id }}" @selected($selectedClientSystemId === (string) $system->id)>{{ $system->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="form-label">Status</label>
            <select class="input" name="status" required>
                @foreach(['draft' => 'Rascunho', 'scheduled' => 'Programado', 'active' => 'Ativo', 'inactive' => 'Inativo', 'archived' => 'Arquivado'] as $key => $label)
                <option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Duração (min)</label>
            <input class="input" type="number" min="5" max="300" name="duration_minutes" value="{{ $durationMinutes }}" required>
        </div>
        <div>
            <label class="form-label">Nota de corte</label>
            <input class="input" type="number" step="0.01" min="0" max="100" name="passing_score" value="{{ $passingScore }}" required>
        </div>
        <div>
            <label class="form-label">Máx. violações</label>
            <input class="input" type="number" min="1" max="20" name="max_violations" value="{{ $maxViolations }}" required>
        </div>
        <div>
            <label class="form-label">Limite de tentativas</label>
            <input class="input" type="number" min="0" max="99" name="max_attempts" value="{{ $maxAttempts }}" required>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Use 0 para ilimitado. Qualquer valor maior que 0 limita a quantidade de tentativas por aluno.
            </div>
        </div>
        <div>
            <label class="form-label">Início programado</label>
            <input class="input" type="datetime-local" name="starts_at" value="{{ $startsAtValue }}">
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Quando o status estiver como Programado, o simulado será ativado automaticamente nesse horário.
            </div>
        </div>
        <div>
            <label class="form-label">Fim programado</label>
            <input class="input" type="datetime-local" name="ends_at" value="{{ $endsAtValue }}">
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Ao ultrapassar esse horário, o simulado poderá ser inativado automaticamente.
            </div>
        </div>

        <div>
            <label class="form-label">Template de e-mail</label>
            <select class="input" name="template_id">
                <option value="">Sem template</option>
                @foreach($templates as $template)
                <option value="{{ $template->id }}" @selected($selectedTemplateId === (string) $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Moodle Course ID</label>
            <input class="input" type="number" min="0" name="moodle_course_id" value="{{ $moodleCourseId }}">
        </div>
        <div>
            <label class="form-label">Moodle Activity ID</label>
            <input class="input" type="number" min="0" name="moodle_activity_id" value="{{ $moodleActivityId }}">
        </div>
        <div>
            <label class="form-label">Moodle Activity Name</label>
            <input class="input" name="moodle_activity_name" value="{{ $moodleActivityName }}">
        </div>
        <div>
            <label class="form-label">Hub / ciclo existente</label>
            <select class="input" name="hub_id">
                <option value="">Criar / usar pelo slug manual</option>
                @foreach($availableHubs as $hub)
                <option value="{{ $hub->id }}" @selected($selectedHubId === (string) $hub->id)>
                    {{ $hub->name }} · {{ $hub->slug }}{{ $hub->clientSystem?->name ? ' · '.$hub->clientSystem->name : '' }}
                </option>
                @endforeach
            </select>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Se selecionar um hub existente, o simulado será vinculado a ele e o slug abaixo vira apenas fallback.
            </div>
        </div>
        <div>
            <label class="form-label">Slug do Link Principal</label>
            <input class="input" list="hub-slug-options" name="public_hub_slug" value="{{ $publicHubSlug }}" placeholder="ex: concurso-inss">
            @if(!empty($hubSlugs))
            <datalist id="hub-slug-options">
                @foreach($hubSlugs as $hubSlug)
                <option value="{{ $hubSlug }}"></option>
                @endforeach
            </datalist>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:5px">
                Sugestões automáticas dos links principais já usados.
            </div>
            @endif
        </div>
        <div>
            <label class="form-label">Rótulo da Semana</label>
            <input class="input" name="weekly_label" value="{{ $weeklyLabel }}" placeholder="ex: Semana 12 - Maio">
        </div>

        <div style="grid-column:1/-1;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;padding:10px;border:1px solid var(--surface-border);border-radius:10px">
            @php
                $flags = [
                    'capture_photo_enabled' => 'Captura inicial de foto',
                    'webcam_enabled' => 'Webcam habilitada',
                    'fullscreen_enabled' => 'Tela cheia obrigatória',
                    'show_result_immediately' => 'Exibir resultado imediato',
                    'auto_email_enabled' => 'Enviar e-mail automático',
                    'moodle_integration_enabled' => 'Integração Moodle ativa',
                    'shuffle_questions' => 'Embaralhar questões',
                    'shuffle_choices' => 'Embaralhar alternativas',
                    'notify_participants_on_save' => 'Notificar participantes por e-mail ao salvar',
                ];
                $flagDefaults = [
                    'capture_photo_enabled' => false,
                    'webcam_enabled' => true,
                    'fullscreen_enabled' => true,
                    'show_result_immediately' => true,
                    'auto_email_enabled' => true,
                    'moodle_integration_enabled' => false,
                    'shuffle_questions' => true,
                    'shuffle_choices' => true,
                    'notify_participants_on_save' => false,
                ];
            @endphp
            @foreach($flags as $field => $label)
            <label style="display:flex;gap:8px;align-items:center;font-size:0.85rem">
                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $flagValueSources[$field] ?? $flagDefaults[$field]))>
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </div>

        <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;margin-top:6px">
            <a href="{{ $simulado ? route('simulados.show', $simulado) : route('simulados.index') }}" class="btn btn-ghost btn-sm">Cancelar</a>
            <button class="btn btn-primary btn-sm" type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection

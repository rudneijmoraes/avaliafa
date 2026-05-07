@extends('layouts.exam')

@section('exam-title', $exam->title)

@section('body')
@php
    $isSimulation = (bool) $session->is_simulation;
    $simuladoSettings = (array) data_get($exam->settings ?? [], 'simulado', []);
    $fullscreenRequired = $isSimulation ? (bool) data_get($simuladoSettings, 'fullscreen_enabled', true) : true;
    $simuladoLayoutMode = (string) data_get($simuladoSettings, 'layout_mode', 'step');
    $singlePageMode = $isSimulation && $simuladoLayoutMode === 'single_page';
    $simuladoIntroText = trim((string) data_get($simuladoSettings, 'intro_text', ''));
    $instructionsHtml = (string) data_get($exam->settings ?? [], 'instructions_html', '');
    $brandingWatermarkText = trim((string) data_get($exam->settings ?? [], 'branding_watermark_text', data_get($simuladoSettings, 'branding_watermark_text', '')));
    $simuladoIntroBlocks = $simuladoIntroText !== ''
        ? collect(preg_split('/\R-{3,}\R/', $simuladoIntroText))
            ->map(fn ($block) => trim((string) $block))
            ->filter(fn ($block) => $block !== '')
            ->values()
        : collect();
    $questionBlocks = collect($questionBlocks ?? []);
    $questionIndexes = collect($questions ?? [])->pluck('id')->flip();
@endphp

{{-- Gate de segurança — overlay bloqueante antes de liberar a prova --}}
<div id="security-gate" x-data="securityGate()" x-show="!ready" x-transition
     style="position:fixed;inset:0;z-index:99999;background:linear-gradient(135deg,#0F2044 0%,#1D4ED8 55%,#0EA5E9 100%);
            display:flex;align-items:center;justify-content:center;padding:24px;">

    <div style="background:white;border-radius:20px;box-shadow:0 24px 80px rgba(0,0,0,0.25);
                max-width:480px;width:100%;padding:36px;text-align:center;">

        {{-- Ícone escudo --}}
        <div style="width:64px;height:64px;background:#EFF6FF;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>

        <h2 style="font-size:1.25rem;font-weight:800;color:#0F172A;margin:0 0 8px">Ativando Modo Seguro</h2>
        <p style="font-size:0.875rem;color:#64748B;margin:0 0 24px;line-height:1.6">
            <span x-show="effectiveFullscreenRequired">Para prosseguir, o sistema precisa ativar a tela cheia</span>
            <span x-show="!effectiveFullscreenRequired">Para prosseguir, o sistema precisa validar os requisitos do navegador</span>
            @if($exam->webcam_enabled ?? false)
                e acessar sua câmera
            @endif
            .
        </p>

        {{-- Status dos checks --}}
        <div style="text-align:left;margin-bottom:24px;">
            <div x-show="!mobileDevice" style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #E2E8F0;">
                <template x-if="fullscreenOk">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </template>
                <template x-if="!fullscreenOk && !fullscreenError">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
                </template>
                <template x-if="fullscreenError">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </template>
                <span style="font-size:0.875rem;" :style="fullscreenOk ? 'color:#065F46;font-weight:600' : (fullscreenError ? 'color:#991B1B' : 'color:#64748B')">
                    <span x-show="effectiveFullscreenRequired && !fullscreenOk && !fullscreenError">Tela cheia — aguardando</span>
                    <span x-show="effectiveFullscreenRequired && fullscreenOk">Tela cheia ativada</span>
                    <span x-show="effectiveFullscreenRequired && fullscreenError">Tela cheia negada — clique novamente</span>
                    <span x-show="!effectiveFullscreenRequired && !mobileDevice && fullscreenOk">Modo tela cheia disponível</span>
                </span>
            </div>

            @if($exam->webcam_enabled ?? false)
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #E2E8F0;">
                <template x-if="webcamOk">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </template>
                <template x-if="!webcamOk && !webcamError">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                </template>
                <template x-if="webcamError">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </template>
                <span style="font-size:0.875rem;" :style="webcamOk ? 'color:#065F46;font-weight:600' : (webcamError ? 'color:#991B1B' : 'color:#64748B')">
                    <span x-show="!webcamOk && !webcamError">Verificando camera...</span>
                    <span x-show="webcamOk">Camera ativada</span>
                    <span x-show="webcamError" x-text="webcamErrorMessage"></span>
                </span>
            </div>
            @endif
        </div>

        {{-- Botão para ativar --}}
        <button @click="activate()" x-show="!ready && effectiveFullscreenRequired"
                style="width:100%;padding:14px;background:linear-gradient(135deg,#1D4ED8,#2563EB);color:white;
                       border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;
                       font-family:'Inter',Arial,sans-serif;box-shadow:0 4px 16px rgba(29,78,216,0.4);
                       display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/>
            </svg>
            <span x-text="webcamError ? 'Tentar ativar novamente' : 'Ativar Tela Cheia e Iniciar'"></span>
        </button>
        <div x-show="!ready && !effectiveFullscreenRequired && !mobileDevice" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <button @click="activate(true)"
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#1D4ED8,#2563EB);color:white;border:none;border-radius:12px;font-size:0.95rem;font-weight:700;cursor:pointer;font-family:'Inter',Arial,sans-serif;box-shadow:0 4px 16px rgba(29,78,216,0.35);">
                Usar tela cheia
            </button>
            <button @click="activate(false)"
                    style="width:100%;padding:12px;background:#E2E8F0;color:#0F172A;border:none;border-radius:12px;font-size:0.95rem;font-weight:700;cursor:pointer;font-family:'Inter',Arial,sans-serif;">
                Não usar tela cheia
            </button>
        </div>
        <button @click="activate(false)" x-show="!ready && mobileDevice"
                style="width:100%;padding:14px;background:linear-gradient(135deg,#1D4ED8,#2563EB);color:white;
                       border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;
                       font-family:'Inter',Arial,sans-serif;box-shadow:0 4px 16px rgba(29,78,216,0.4);
                       display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;">
            Iniciar prova
        </button>

        {{-- Mensagem enquanto ativa --}}
        <p x-show="fullscreenOk && !ready" style="font-size:0.8rem;color:#64748B;margin:12px 0 0">
            Preparando ambiente seguro...
        </p>
    </div>
</div>

<div x-data="examApp()" x-init="init()">

    {{-- Topbar da prova --}}
    <header class="exam-topbar">
        <div class="exam-logo">
            <div class="exam-logo-icon">A</div>
            <span>AvaliaFA</span>
        </div>

        <div class="exam-title-bar">
            <div class="exam-title-bar-name">{{ $exam->title }}</div>
            <div class="exam-title-bar-sub">{{ $student->name }}</div>
        </div>

        <div class="exam-topbar-right">
            {{-- Progresso numérico --}}
            <span class="exam-progress-meta" style="font-size:0.8rem;color:var(--text-secondary);white-space:nowrap">
                @if($singlePageMode)
                Questões <strong>{{ count($questions) }}</strong>
                @else
                Questão <strong x-text="currentIndex + 1"></strong> de <strong>{{ count($questions) }}</strong>
                @endif
            </span>

            {{-- Timer --}}
            <div class="exam-timer"
                 :class="{ 'warning': timeLeft < 600 && timeLeft >= 300, 'urgent': timeLeft < 300 }">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span x-text="formatTime(timeLeft)"></span>
            </div>

            {{-- Autosave --}}
            <div class="autosave-indicator">
                <svg x-show="!isOnline" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 8.82a15.91 15.91 0 0 1 20 0"/><path d="M5 12.86a10.94 10.94 0 0 1 14 0"/><path d="M8.5 16.43a5.94 5.94 0 0 1 7 0"/><line x1="2" y1="2" x2="22" y2="22"/>
                </svg>
                <svg x-show="saving" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.8s linear infinite">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                <svg x-show="isOnline && !saving && !syncPending" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--color-success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <span x-text="saveStatusLabel"></span>
            </div>
            <div class="autosave-indicator" x-show="securityQueuePending > 0 || !securityQueueOnline">
                <span x-text="securityQueueLabel"></span>
            </div>
            <div class="autosave-indicator" x-show="snapshotQueuePending > 0 || !securityQueueOnline">
                <span x-text="snapshotQueueLabel"></span>
            </div>
        </div>
    </header>

    {{-- Barra de progresso --}}
    <div class="exam-progress-bar">
        <div class="exam-progress-fill"
             :style="'width:' + ((answeredCount / {{ count($questions) }}) * 100) + '%'"></div>
    </div>

    {{-- Layout principal --}}
    <div class="exam-layout">

        <aside class="exam-sidebar">
            <div class="exam-sidebar-label">Navegação</div>

            {{-- Status legend --}}
            <div class="exam-status-row">
                <span class="exam-status-item">
                    <span class="dot" style="background:var(--color-primary-500)"></span> Atual
                </span>
                <span class="exam-status-item">
                    <span class="dot" style="background:var(--color-success)"></span> Respondida
                </span>
                <span class="exam-status-item">
                    <span class="dot" style="background:var(--exam-border)"></span> Não resp.
                </span>
            </div>

            {{-- Grade de questões --}}
            <div class="exam-nav-grid">
                @foreach($questions as $i => $q)
                <button
                    class="exam-nav-btn"
                    :class="{
                        'current':    currentIndex === {{ $i }},
                        'answered':   currentIndex !== {{ $i }} && isQuestionAnswered({{ $q->id }}),
                        'unanswered': currentIndex !== {{ $i }} && !isQuestionAnswered({{ $q->id }})
                    }"
                    @click="goToQuestion({{ $i }})"
                    title="Questão {{ $i + 1 }}"
                >{{ $i + 1 }}</button>
                @endforeach
            </div>

            <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:16px;padding:0 2px">
                <span x-text="answeredCount"></span> de {{ count($questions) }} respondidas
            </div>

            {{-- Botão sair --}}
            <button @click="confirmExit = true"
                    style="width:100%;margin-top:8px;padding:10px;border:1.5px solid var(--exam-border);border-radius:10px;
                           background:transparent;font-size:0.8rem;font-weight:600;cursor:pointer;color:var(--text-muted);
                           font-family:inherit;display:flex;align-items:center;justify-content:center;gap:6px;transition:all 0.2s"
                    onmouseover="this.style.borderColor='#EF4444';this.style.color='#EF4444'"
                    onmouseout="this.style.borderColor='var(--exam-border)';this.style.color='var(--text-muted)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sair da Prova
            </button>

            <div style="margin-top:16px;margin-bottom:12px;border-top:1px solid #E2E8F0;padding-top:12px">
                <div style="height:112px;border-radius:14px;background:#FFFFFF;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;padding:5px 6px">
                    <img src="{{ asset('imagem/triade-anasps.png') }}" alt="Tríade Anasps" style="max-width:100%;max-height:100%;object-fit:contain">
                </div>
            </div>

            @if($exam->webcam_enabled ?? false)
            <div style="margin-top:16px;border:1px solid var(--exam-border);border-radius:10px;overflow:hidden">
                <div style="font-size:0.65rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;padding:8px 10px;border-bottom:1px solid var(--exam-border)">Webcam</div>
                <video id="webcam-preview" autoplay muted playsinline style="width:100%;height:80px;object-fit:cover;background:#0F172A;display:block"></video>
            </div>
            @endif
        </aside>

        {{-- Área principal --}}
        <main class="exam-main">
            @if(filled($instructionsHtml))
            <div class="question-card" style="margin-bottom:16px;border:1px solid #C7D2FE">
                <div class="question-header branded-header" style="background:#EEF2FF">
                    @if($brandingWatermarkText !== '')
                    <div class="question-watermark">{{ $brandingWatermarkText }}</div>
                    @endif
                    <div>
                        <span class="question-number">Orientações da prova</span>
                    </div>
                </div>
                <div class="question-body">
                    <div class="question-text question-content" style="line-height:1.7">
                        {!! $instructionsHtml !!}
                    </div>
                </div>
            </div>
            @endif

            @if($isSimulation && $simuladoIntroBlocks->count() > 0)
            @foreach($simuladoIntroBlocks as $introIndex => $introBlock)
            <div class="question-card" style="margin-bottom:14px;border:1px solid #BFDBFE;{{ $singlePageMode ? '' : 'position:sticky;top:10px;z-index:5' }}">
                <div class="question-header branded-header" style="background:#EFF6FF">
                    @if($brandingWatermarkText !== '')
                    <div class="question-watermark">{{ $brandingWatermarkText }}</div>
                    @endif
                    <div>
                        <span class="question-number">Texto-base {{ $simuladoIntroBlocks->count() > 1 ? ($introIndex + 1) : '' }} para as questões</span>
                    </div>
                </div>
                <div class="question-body">
                    <div class="question-text question-content" style="white-space:pre-wrap;line-height:1.6">
                        {!! nl2br(e($introBlock)) !!}
                    </div>
                </div>
            </div>
            @endforeach
            @endif

            @php
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            @endphp

            @if($questionBlocks->isNotEmpty())
                @foreach($questionBlocks as $block)
                    @php
                        $blockIndexes = $block->questions
                            ->map(fn ($question) => (int) ($questionIndexes[$question->id] ?? -1))
                            ->filter(fn ($index) => $index >= 0)
                            ->values();
                    @endphp
                    <section
                        @if(! $singlePageMode)
                        x-show='{{ Js::from($blockIndexes->all()) }}.includes(currentIndex)'
                        @endif
                        style="margin-bottom:18px"
                    >
                        @if(filled($block->base_text))
                        <div class="question-card" style="margin-bottom:14px;border:1px solid #BFDBFE;{{ $singlePageMode ? '' : 'position:sticky;top:10px;z-index:5' }}">
                            <div class="question-header branded-header" style="background:#EFF6FF">
                                @if($brandingWatermarkText !== '')
                                <div class="question-watermark">{{ $brandingWatermarkText }}</div>
                                @endif
                                <div>
                                    <span class="question-number">{{ $block->title ?: 'Texto-base do bloco' }}</span>
                                </div>
                            </div>
                            <div class="question-body">
                                <div class="question-text question-content" style="line-height:1.7">
                                    {!! $block->base_text !!}
                                </div>
                            </div>
                        </div>
                        @endif

                        @foreach($block->questions as $q)
                            @php
                                $i = (int) ($questionIndexes[$q->id] ?? -1);
                            @endphp
                            @if($i >= 0)
                            <div
                                 @if(! $singlePageMode)
                                 x-show="currentIndex === {{ $i }}"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 @endif
                            >
                                <div class="question-card" id="question-card-{{ $i }}">
                                    <div class="question-header branded-header">
                                        @if($brandingWatermarkText !== '')
                                        <div class="question-watermark">{{ $brandingWatermarkText }}</div>
                                        @endif
                                        <div>
                                            <span class="question-number">Questão {{ $i + 1 }} de {{ count($questions) }}</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            @if(! $session->is_simulation && $q->difficulty)
                                            <span style="font-size:0.72rem;font-weight:600;padding:3px 10px;border-radius:9999px;
                                                {{ $q->difficulty === 'hard' ? 'background:#FEE2E2;color:#EF4444' : ($q->difficulty === 'medium' ? 'background:#FEF3C7;color:#F59E0B' : 'background:#D1FAE5;color:#10B981') }}">
                                                {{ match($q->difficulty) { 'easy' => 'Fácil', 'medium' => 'Média', 'hard' => 'Difícil', default => ucfirst($q->difficulty) } }}
                                            </span>
                                            @endif
                                            <span style="font-size:0.72rem;color:var(--text-muted)">
                                                {{ match($q->type) { 'multiple_choice' => 'Múltipla escolha', 'true_false' => 'Verdadeiro/Falso', 'multiple_answer' => 'Múltiplas respostas', default => 'Questão' } }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="question-body">
                                        <div class="question-text question-content">
                                            {!! $q->content !!}
                                        </div>

                                        <div class="choice-list">
                                            @foreach($q->choices as $ci => $choice)
                                            @php
                                                $isMultiple = $q->type === 'multiple_answer';
                                            @endphp
                                            <label
                                                class="choice-item"
                                                :class="{ 'selected': isChoiceSelected({{ $q->id }}, {{ $choice->id }}, {{ $isMultiple ? 'true' : 'false' }}) }"
                                                @click="selectAnswer({{ $q->id }}, {{ $choice->id }}, {{ $isMultiple ? 'true' : 'false' }})"
                                                style="cursor:pointer"
                                            >
                                                @if($isMultiple)
                                                <div style="width:18px;height:18px;border-radius:5px;border:2px solid var(--exam-border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;transition:all 0.15s"
                                                     :style="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, true) ? 'border-color:#2563EB;background:#2563EB' : ''">
                                                    <svg x-show="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, true)"
                                                         width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"/>
                                                    </svg>
                                                </div>
                                                @else
                                                <div class="choice-radio"
                                                     :style="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, false) ? 'border-color:#2563EB;background:#2563EB' : ''">
                                                    <span x-show="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, false)"
                                                          style="width:6px;height:6px;border-radius:50%;background:white;display:block"></span>
                                                </div>
                                                @endif

                                                <div class="choice-letter"
                                                     :style="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, {{ $isMultiple ? 'true' : 'false' }}) ? 'background:#2563EB;color:white' : ''">
                                                    {{ $letters[$ci] ?? $ci + 1 }}
                                                </div>

                                                <span class="choice-text question-content">{!! $choice->content !!}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if(! $singlePageMode)
                                    <div class="question-nav">
                                        <button class="btn-nav"
                                                @click="currentIndex--"
                                                :disabled="currentIndex === 0">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="15 18 9 12 15 6"/>
                                            </svg>
                                            Anterior
                                        </button>

                                        <span style="font-size:0.75rem;color:var(--text-muted)">
                                            <span x-text="answeredCount"></span> / {{ count($questions) }} respondidas
                                        </span>

                                        @if($i < count($questions) - 1)
                                        <button class="btn-nav"
                                                @click="currentIndex++"
                                                :disabled="currentIndex === {{ count($questions) - 1 }}">
                                            Próxima
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="9 18 15 12 9 6"/>
                                            </svg>
                                        </button>
                                        @else
                                        <button class="btn-nav" @click="confirmSubmit = true"
                                                style="background:linear-gradient(135deg,#1D4ED8,#2563EB);color:white;border-color:#1D4ED8;box-shadow:0 2px 8px rgba(29,78,216,0.3)">
                                            Entregar
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </section>
                @endforeach
            @else
                @foreach($questions as $i => $q)
                <div
                     @if(! $singlePageMode)
                     x-show="currentIndex === {{ $i }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     @endif
                >

                    <div class="question-card" id="question-card-{{ $i }}">
                        <div class="question-header branded-header">
                            @if($brandingWatermarkText !== '')
                            <div class="question-watermark">{{ $brandingWatermarkText }}</div>
                            @endif
                            <div>
                                <span class="question-number">Questão {{ $i + 1 }} de {{ count($questions) }}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px">
                                @if(! $session->is_simulation && $q->difficulty)
                                <span style="font-size:0.72rem;font-weight:600;padding:3px 10px;border-radius:9999px;
                                    {{ $q->difficulty === 'hard' ? 'background:#FEE2E2;color:#EF4444' : ($q->difficulty === 'medium' ? 'background:#FEF3C7;color:#F59E0B' : 'background:#D1FAE5;color:#10B981') }}">
                                    {{ match($q->difficulty) { 'easy' => 'Fácil', 'medium' => 'Média', 'hard' => 'Difícil', default => ucfirst($q->difficulty) } }}
                                </span>
                                @endif
                                <span style="font-size:0.72rem;color:var(--text-muted)">
                                    {{ match($q->type) { 'multiple_choice' => 'Múltipla escolha', 'true_false' => 'Verdadeiro/Falso', 'multiple_answer' => 'Múltiplas respostas', default => 'Questão' } }}
                                </span>
                            </div>
                        </div>

                        <div class="question-body">
                            <div class="question-text question-content">
                                {!! $q->content !!}
                            </div>

                            <div class="choice-list">
                                @foreach($q->choices as $ci => $choice)
                                @php
                                    $isMultiple = $q->type === 'multiple_answer';
                                @endphp
                                <label
                                    class="choice-item"
                                    :class="{ 'selected': isChoiceSelected({{ $q->id }}, {{ $choice->id }}, {{ $isMultiple ? 'true' : 'false' }}) }"
                                    @click="selectAnswer({{ $q->id }}, {{ $choice->id }}, {{ $isMultiple ? 'true' : 'false' }})"
                                    style="cursor:pointer"
                                >
                                    @if($isMultiple)
                                    <div style="width:18px;height:18px;border-radius:5px;border:2px solid var(--exam-border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;transition:all 0.15s"
                                         :style="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, true) ? 'border-color:#2563EB;background:#2563EB' : ''">
                                        <svg x-show="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, true)"
                                             width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </div>
                                    @else
                                    <div class="choice-radio"
                                         :style="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, false) ? 'border-color:#2563EB;background:#2563EB' : ''">
                                        <span x-show="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, false)"
                                              style="width:6px;height:6px;border-radius:50%;background:white;display:block"></span>
                                    </div>
                                    @endif

                                    <div class="choice-letter"
                                         :style="isChoiceSelected({{ $q->id }}, {{ $choice->id }}, {{ $isMultiple ? 'true' : 'false' }}) ? 'background:#2563EB;color:white' : ''">
                                        {{ $letters[$ci] ?? $ci + 1 }}
                                    </div>

                                    <span class="choice-text question-content">{!! $choice->content !!}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        @if(! $singlePageMode)
                        <div class="question-nav">
                            <button class="btn-nav"
                                    @click="currentIndex--"
                                    :disabled="currentIndex === 0">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                                Anterior
                            </button>

                            <span style="font-size:0.75rem;color:var(--text-muted)">
                                <span x-text="answeredCount"></span> / {{ count($questions) }} respondidas
                            </span>

                            @if($i < count($questions) - 1)
                            <button class="btn-nav"
                                    @click="currentIndex++"
                                    :disabled="currentIndex === {{ count($questions) - 1 }}">
                                Próxima
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </button>
                            @else
                            <button class="btn-nav" @click="confirmSubmit = true"
                                    style="background:linear-gradient(135deg,#1D4ED8,#2563EB);color:white;border-color:#1D4ED8;box-shadow:0 2px 8px rgba(29,78,216,0.3)">
                                Entregar
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            @endif

            {{-- Botão entregar ao final das questões --}}
            <div style="margin-top:24px;display:flex;justify-content:flex-end">
                <button class="btn-submit-exam" style="width:auto;min-width:220px;padding:13px 28px;font-size:0.95rem" @click="confirmSubmit = true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline;margin-right:6px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    {{ $isSimulation ? 'Entregar Simulado' : 'Entregar Prova' }}
                </button>
            </div>

        </main>
    </div>

    {{-- Modal de confirmação de entrega --}}
    <div x-show="confirmSubmit" class="modal-overlay" x-transition style="display:none">
        <div class="modal-box modal-box-wide">
            <div class="submit-modal-layout">
                <div class="submit-modal-copy">
                    <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:18px">
                        <div style="width:56px;height:56px;background:#EFF6FF;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size:1.35rem;font-weight:800;color:#0F172A;margin:0">{{ $isSimulation ? 'Entregar simulado?' : 'Entregar Prova?' }}</h2>
                            <p style="font-size:0.92rem;color:#64748B;margin:8px 0 0;line-height:1.65">
                                Você respondeu <strong x-text="answeredCount"></strong> de <strong>{{ count($questions) }}</strong> questões.
                                Ao confirmar, a tentativa será enviada para correção e não poderá ser reaberta.
                            </p>
                        </div>
                    </div>

                    <form id="submit-form" method="POST" action="{{ route('exam.submit', $session->id) }}">
                        @csrf
                        <input type="hidden" name="answers" :value="JSON.stringify(answers)">
                    </form>

                    <div class="submit-modal-actions">
                        <button type="button"
                                @click="confirmSubmit = false"
                                :disabled="submittingExam"
                                class="exam-modal-btn exam-modal-btn-secondary">
                            Revisar novamente
                        </button>
                        <button type="button"
                                @click="submitExam()"
                                :disabled="submittingExam"
                                class="exam-modal-btn exam-modal-btn-primary">
                            <span x-show="!submittingExam">Confirmar Entrega</span>
                            <span x-show="submittingExam" style="display:inline-flex;align-items:center;gap:8px">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="animation:spin 0.8s linear infinite">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                </svg>
                                {{ $isSimulation ? 'Entregando simulado...' : 'Entregando prova...' }}
                            </span>
                        </button>
                    </div>
                </div>

                <aside class="submit-modal-aside">
                    <div style="font-size:0.72rem;font-weight:800;color:#64748B;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px">Resumo da tentativa</div>

                    @if(count($questions) > 0)
                    <div style="background:#FFFFFF;border:1px solid #E2E8F0;border-radius:12px;padding:14px;margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.875rem">
                            <span style="color:#64748B">Respondidas</span>
                            <strong x-text="answeredCount + ' / {{ count($questions) }}'"></strong>
                        </div>
                        <div style="height:6px;background:#E2E8F0;border-radius:9999px;margin-top:10px;overflow:hidden">
                            <div style="height:100%;background:linear-gradient(90deg,#10B981,#34D399);border-radius:9999px;transition:width 0.3s ease"
                                 :style="'width:' + ((answeredCount / {{ count($questions) }}) * 100) + '%'"></div>
                        </div>
                    </div>
                    @endif

                    <div style="background:#FFFFFF;border:1px solid #E2E8F0;border-radius:12px;padding:14px">
                        <div style="font-size:0.8rem;color:#475569;line-height:1.55">
                            O sistema grava automaticamente suas respostas.
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    {{-- Modal de confirmação de saída voluntária --}}
    <div x-show="confirmExit" class="modal-overlay" x-transition style="display:none">
        <div class="modal-box">
            <div style="text-align:center;margin-bottom:20px">
                <div style="width:56px;height:56px;background:#FEF2F2;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <h2 style="font-size:1.25rem;font-weight:800;color:#0F172A;margin:0">Deseja sair da prova?</h2>
                <p style="font-size:0.875rem;color:#64748B;margin:8px 0 0;line-height:1.6">
                    Ao sair, sua prova sera <strong>entregue automaticamente</strong> com as respostas ja salvas.
                    Voce nao podera retornar depois.
                </p>
            </div>

            <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px;margin-bottom:20px">
                <div style="display:flex;align-items:start;gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div style="font-size:0.8rem;color:#991B1B;line-height:1.5">
                        <strong>Atencao:</strong> Voce respondeu <strong x-text="answeredCount"></strong> de <strong>{{ count($questions) }}</strong> questoes.
                        Questoes nao respondidas serao consideradas em branco e nao receberao pontuacao.
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px">
                <button @click="confirmExit = false"
                        class="exam-modal-btn exam-modal-btn-primary">
                    Revisar novamente
                </button>
                <button @click="exitExam()"
                        class="exam-modal-btn exam-modal-btn-danger">
                    Sair e Entregar
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function inspectGateWebcamFrame(video) {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });

        if (!context) {
            return { obstructed: false, averageBrightness: 0, dynamicRange: 0 };
        }

        canvas.width = 160;
        canvas.height = 120;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        let totalBrightness = 0;
        let minBrightness = 255;
        let maxBrightness = 0;
        let samples = 0;

        for (let index = 0; index < pixels.length; index += 16) {
            const brightness = (pixels[index] + pixels[index + 1] + pixels[index + 2]) / 3;
            totalBrightness += brightness;
            minBrightness = Math.min(minBrightness, brightness);
            maxBrightness = Math.max(maxBrightness, brightness);
            samples++;
        }

        const averageBrightness = samples > 0 ? totalBrightness / samples : 0;
        const dynamicRange = maxBrightness - minBrightness;
        const obstructed = dynamicRange < 18 && (averageBrightness < 32 || averageBrightness > 223);

        return { obstructed, averageBrightness, dynamicRange };
    }

    async function validateGateWebcamVisibility(stream) {
        const video = document.createElement('video');
        video.muted = true;
        video.playsInline = true;
        video.srcObject = stream;

        const waitForFrame = async () => {
            await video.play();
            await new Promise((resolve) => window.setTimeout(resolve, 260));
            return inspectGateWebcamFrame(video);
        };

        try {
            if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                return await waitForFrame();
            }

            await new Promise((resolve, reject) => {
                video.onloadedmetadata = resolve;
                video.onerror = reject;
            });

            return await waitForFrame();
        } finally {
            video.pause();
            video.srcObject = null;
        }
    }

    function securityGate() {
        const webcamRequired = {{ ($exam->webcam_enabled ?? false) ? 'true' : 'false' }};
        const fullscreenBaseRequired = {{ $fullscreenRequired ? 'true' : 'false' }};

        return {
            ready: false,
            mobileDevice: false,
            fullscreenRequired: fullscreenBaseRequired,
            fullscreenOk: false,
            fullscreenError: false,
            webcamOk: false,
            webcamError: false,
            webcamErrorMessage: 'Camera nao disponivel - permita o acesso e tente novamente.',

            get effectiveFullscreenRequired() {
                return this.fullscreenRequired && !this.mobileDevice;
            },

            detectMobileDevice() {
                const ua = navigator.userAgent || '';
                const mobileUa = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
                const narrowViewport = window.matchMedia('(max-width: 768px)').matches;

                return mobileUa || narrowViewport;
            },

            async activate(forceFullscreen = null) {
                this.fullscreenError = false;
                this.webcamError = false;
                this.webcamErrorMessage = 'Camera nao disponivel - permita o acesso e tente novamente.';

                // 1. Pedir tela cheia (precisa ser disparado por click do usuario)
                if (this.effectiveFullscreenRequired || forceFullscreen === true) {
                    const el = document.documentElement;
                    const reqFn = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen;

                    if (reqFn) {
                        try {
                            await reqFn.call(el);
                            this.fullscreenOk = true;
                        } catch (e) {
                            this.fullscreenError = true;
                            return;
                        }
                    } else if (this.effectiveFullscreenRequired) {
                        this.fullscreenError = true;
                        return;
                    }
                } else {
                    this.fullscreenOk = true;
                }

                // 2. Pedir webcam (se necessario)
                if (webcamRequired && !this.webcamOk) {
                    let stream = null;

                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        const inspection = await validateGateWebcamVisibility(stream);

                        if (inspection.obstructed) {
                            stream.getTracks().forEach((track) => track.stop());
                            this.webcamError = true;
                            this.webcamErrorMessage = 'Camera obstruida - remova a tampa ou melhore a iluminacao e tente novamente.';
                            return;
                        }

                        // Manter stream ativo para o SecureExamEngine reusar
                        window.__avaliaWebcamStream = stream;
                        this.webcamOk = true;
                    } catch (e) {
                        this.webcamError = true;
                        this.webcamErrorMessage = 'Camera nao disponivel - permita o acesso e tente novamente.';
                        return;
                    }
                }

                // 3. Tudo ok — liberar a prova
                this.ready = true;
                window.dispatchEvent(new CustomEvent('secureexam:gate-ready'));

                // Remover o overlay do DOM depois da transicao
                setTimeout(() => {
                    const gate = document.getElementById('security-gate');
                    if (gate) gate.remove();
                }, 500);
            },

            init() {
                this.mobileDevice = this.detectMobileDevice();

                if (!this.effectiveFullscreenRequired) {
                    this.fullscreenOk = true;
                }

                if (!webcamRequired) {
                    this.webcamOk = true;
                }
            }
        };
    }

    function examApp() {
        return {
            currentIndex: 0,
            timeLeft:     {{ $session->getRemainingSeconds() ?? ($exam->duration_minutes * 60) }},
            questionIds:  @json(collect($questions ?? [])->pluck('id')->values()->all()),
            answers:      @json($savedAnswers ?? []),
            saving:       false,
            lastSaved:    null,
            confirmSubmit: false,
            confirmExit: false,
            submittingExam: false,
            isOnline: navigator.onLine,
            syncPending: false,
            db: null,
            sessionId: {{ $session->id }},
            securityQueuePending: 0,
            securityQueueOnline: navigator.onLine,
            snapshotQueuePending: 0,
            timerHandle: null,
            timerDeadlineMs: null,

            get answeredCount() {
                return this.questionIds.filter(questionId => this.isQuestionAnswered(questionId)).length;
            },

            isQuestionAnswered(questionId) {
                const value = this.answers[questionId];

                if (Array.isArray(value)) {
                    return value.length > 0;
                }

                return value !== null && value !== undefined && value !== '';
            },

            isChoiceSelected(questionId, choiceId, isMultiple) {
                const value = this.answers[questionId];

                if (isMultiple) {
                    return Array.isArray(value) && value.includes(choiceId);
                }

                return value !== null && value !== undefined && String(value) === String(choiceId);
            },

            get saveStatusLabel() {
                if (!this.isOnline && this.lastSaved) {
                    return 'Offline · salvo local às ' + this.lastSaved;
                }
                if (!this.isOnline) {
                    return 'Offline · salvamento local ativo';
                }
                if (this.saving) {
                    return 'Salvando...';
                }
                if (this.syncPending) {
                    return 'Conexão restabelecida, sincronizando...';
                }
                return this.lastSaved ? 'Salvo ' + this.lastSaved : 'Salvando...';
            },

            get securityQueueLabel() {
                if (!this.securityQueueOnline) {
                    return 'Eventos de segurança serão enviados quando a conexão voltar';
                }

                if (this.securityQueuePending > 0) {
                    return this.securityQueuePending + ' evento(s) de segurança pendente(s) de sincronização';
                }

                return '';
            },

            get snapshotQueueLabel() {
                if (!this.securityQueueOnline) {
                    return 'Snapshots serão enviados quando a conexão voltar';
                }

                if (this.snapshotQueuePending > 0) {
                    return this.snapshotQueuePending + ' snapshot(s) pendente(s) de upload';
                }

                return '';
            },

            init() {
                this.bootstrapAnswers();
                this.startTimer();
                this.scheduleAutosave();
                this.initOfflineMode();
                this.bindScrollTracker();

                window.addEventListener('online', () => {
                    this.isOnline = true;
                    this.flushOfflineAnswers();
                });

                window.addEventListener('offline', () => {
                    this.isOnline = false;
                });

                window.addEventListener('beforeunload', () => {
                    this.storeOfflineAnswers();
                });

                window.addEventListener('secureexam:queue-status', (event) => {
                    this.securityQueuePending = Number(event.detail?.security_pending ?? 0);
                    this.snapshotQueuePending = Number(event.detail?.snapshot_pending ?? 0);
                    this.securityQueueOnline = Boolean(event.detail?.online ?? navigator.onLine);
                });

                if (window.SecureExamEngine?.getQueueStatus) {
                    const status = window.SecureExamEngine.getQueueStatus();
                    this.securityQueuePending = Number(status.security_pending ?? status.pending ?? 0);
                    this.snapshotQueuePending = Number(status.snapshot_pending ?? 0);
                    this.securityQueueOnline = Boolean(status.online ?? navigator.onLine);
                }

                const video = document.getElementById('webcam-preview');
                if (video) {
                    // Reuse stream from security gate or SecureExamEngine
                    if (window.__avaliaWebcamStream) {
                        video.srcObject = window.__avaliaWebcamStream;
                    } else {
                        navigator.mediaDevices?.getUserMedia({ video: true, audio: false })
                            .then(stream => { video.srcObject = stream; })
                            .catch(() => {});
                    }
                }
            },

            bootstrapAnswers() {
                const normalizedAnswers = {};

                this.questionIds.forEach((questionId) => {
                    const directValue = this.answers[questionId];
                    const stringKeyValue = this.answers[String(questionId)];
                    const value = directValue !== undefined ? directValue : (stringKeyValue !== undefined ? stringKeyValue : null);

                    normalizedAnswers[questionId] = Array.isArray(value) ? [...value] : value;
                });

                this.answers = normalizedAnswers;
            },

            goToQuestion(index) {
                this.currentIndex = index;

                if (!{{ $singlePageMode ? 'true' : 'false' }}) {
                    return;
                }

                const target = document.getElementById('question-card-' + index);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            },

            bindScrollTracker() {
                if (!{{ $singlePageMode ? 'true' : 'false' }}) {
                    return;
                }

                const updateCurrentIndex = () => {
                    let bestIndex = 0;
                    let bestDistance = Number.POSITIVE_INFINITY;

                    this.questionIds.forEach((questionId, index) => {
                        const element = document.getElementById('question-card-' + index);
                        if (!element) {
                            return;
                        }

                        const rect = element.getBoundingClientRect();
                        const distance = Math.abs(rect.top - 120);

                        if (distance < bestDistance) {
                            bestDistance = distance;
                            bestIndex = index;
                        }
                    });

                    this.currentIndex = bestIndex;
                };

                updateCurrentIndex();
                window.addEventListener('scroll', updateCurrentIndex, { passive: true });
            },

            selectAnswer(questionId, choiceId, isMultiple) {
                if (isMultiple) {
                    if (!Array.isArray(this.answers[questionId])) {
                        this.answers[questionId] = [];
                    }
                    const idx = this.answers[questionId].indexOf(choiceId);
                    if (idx === -1) this.answers[questionId].push(choiceId);
                    else this.answers[questionId].splice(idx, 1);
                } else {
                    this.answers[questionId] = choiceId;
                }

                this.answers = { ...this.answers };
                this.scheduleSave();
            },

            formatTime(seconds) {
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            },

            startTimer() {
                if (this.timerHandle) {
                    clearInterval(this.timerHandle);
                }

                if (!this.timerDeadlineMs) {
                    this.timerDeadlineMs = Date.now() + (Math.max(0, Number(this.timeLeft) || 0) * 1000);
                }

                let submittedByTimer = false;
                const tick = () => {
                    const remainingSeconds = Math.max(0, Math.ceil((this.timerDeadlineMs - Date.now()) / 1000));
                    this.timeLeft = remainingSeconds;

                    if (remainingSeconds <= 0 && !submittedByTimer) {
                        submittedByTimer = true;
                        clearInterval(this.timerHandle);
                        this.timerHandle = null;
                        this.submitExam(true);
                    }
                };

                tick();
                this.timerHandle = setInterval(tick, 250);
            },

            scheduleSave: (() => {
                let t;
                return function () {
                    clearTimeout(t);
                    t = setTimeout(() => this.saveProgress(), 3000);
                };
            })(),

            scheduleAutosave() {
                setInterval(() => this.saveProgress(), 30000);
            },

            async saveProgress(force = false) {
                if (!force && this.saving) {
                    return false;
                }

                this.saving = true;
                try {
                    await this.storeOfflineAnswers();

                    if (!this.isOnline) {
                        this.syncPending = true;
                        return false;
                    }

                    const res = await fetch('{{ route('exam.save-progress', $session->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ answers: this.answers }),
                        keepalive: true,
                    });
                    if (res.ok) {
                        const now = new Date();
                        this.lastSaved = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        this.syncPending = false;
                        await this.clearOfflineAnswers();
                        return true;
                    }
                    this.syncPending = true;
                    return false;
                } catch (e) {
                    this.syncPending = true;
                    return false;
                }
                finally { this.saving = false; }
            },

            async withSnapshotTimeout(timeoutMs = 4500) {
                if (!window.SecureExamEngine?.captureSnapshot) {
                    return false;
                }

                return await Promise.race([
                    window.SecureExamEngine.captureSnapshot('end'),
                    new Promise((resolve) => window.setTimeout(() => resolve(false), timeoutMs)),
                ]).catch(() => false);
            },

            async submitExam(forceByTimer = false) {
                if (this.submittingExam) {
                    return;
                }

                if (!navigator.onLine) {
                    if (!forceByTimer) {
                        alert('{{ $isSimulation ? 'Você está offline. Conecte-se à internet para entregar o simulado.' : 'Você está offline. Conecte-se à internet para entregar a prova.' }}');
                    }
                    return;
                }

                this.submittingExam = true;

                try {
                    await this.saveProgress(true);
                    await this.withSnapshotTimeout();
                    document.getElementById('submit-form').submit();
                } catch (e) {
                    this.submittingExam = false;
                    alert('Não foi possível concluir a entrega agora. Tente novamente.');
                }
            },

            async exitExam() {
                if (this.submittingExam) {
                    return;
                }

                // Register voluntary exit security event
                try {
                    const apiBase = document.querySelector('meta[name="avalia-fa-exam"]')?.dataset?.apiBase || '/exam';
                    await fetch(`${apiBase}/sessions/${this.sessionId}/security-event`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            type: 'voluntary_exit',
                            metadata: {
                                answered_count: this.answeredCount,
                                total_questions: {{ count($questions) }},
                                time_remaining: this.timeLeft,
                            },
                        }),
                        keepalive: true,
                    });
                } catch (e) {
                    // Continue with exit even if event fails
                }

                this.submittingExam = true;

                try {
                    await this.saveProgress(true);
                    await this.withSnapshotTimeout();
                    document.getElementById('submit-form').submit();
                } catch (e) {
                    this.submittingExam = false;
                    alert('Não foi possível concluir a entrega agora. Tente novamente.');
                }
            },

            async initOfflineMode() {
                this.db = await this.openDb();

                if (!this.db) {
                    return;
                }

                const saved = await this.getOfflineAnswers();

                if (!saved || !saved.answers) {
                    return;
                }

                this.answers = Object.keys(saved.answers).length > 0 ? saved.answers : this.answers;
                this.syncPending = true;
                this.lastSaved = this.formatSavedTime(saved.updated_at);

                if (navigator.onLine) {
                    await this.flushOfflineAnswers();
                }
            },

            async flushOfflineAnswers() {
                if (!this.syncPending) {
                    return;
                }

                await this.saveProgress(true);
            },

            openDb() {
                return new Promise((resolve) => {
                    if (!window.indexedDB) {
                        resolve(null);
                        return;
                    }

                    const request = window.indexedDB.open('avaliafa-offline', 1);

                    request.onupgradeneeded = () => {
                        const db = request.result;
                        if (!db.objectStoreNames.contains('exam_answers')) {
                            db.createObjectStore('exam_answers', { keyPath: 'session_id' });
                        }
                    };

                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => resolve(null);
                });
            },

            storeOfflineAnswers() {
                return new Promise((resolve) => {
                    if (!this.db) {
                        resolve(false);
                        return;
                    }

                    const tx = this.db.transaction('exam_answers', 'readwrite');
                    tx.objectStore('exam_answers').put({
                        session_id: this.sessionId,
                        answers: this.answers,
                        updated_at: Date.now(),
                    });
                    tx.oncomplete = () => resolve(true);
                    tx.onerror = () => resolve(false);
                });
            },

            getOfflineAnswers() {
                return new Promise((resolve) => {
                    if (!this.db) {
                        resolve(null);
                        return;
                    }

                    const tx = this.db.transaction('exam_answers', 'readonly');
                    const request = tx.objectStore('exam_answers').get(this.sessionId);
                    request.onsuccess = () => resolve(request.result ?? null);
                    request.onerror = () => resolve(null);
                });
            },

            clearOfflineAnswers() {
                return new Promise((resolve) => {
                    if (!this.db) {
                        resolve(false);
                        return;
                    }

                    const tx = this.db.transaction('exam_answers', 'readwrite');
                    tx.objectStore('exam_answers').delete(this.sessionId);
                    tx.oncomplete = () => resolve(true);
                    tx.onerror = () => resolve(false);
                });
            },

            formatSavedTime(timestamp) {
                if (!timestamp) {
                    return null;
                }

                return new Date(timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            },
        };
    }
</script>
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .branded-header {
        position: relative;
        overflow: hidden;
    }
    .question-watermark {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        font-size: clamp(1.1rem, 2vw, 1.5rem);
        font-weight: 800;
        letter-spacing: 0.12em;
        color: #1E40AF;
        opacity: 0.16;
        pointer-events: none;
        white-space: nowrap;
        user-select: none;
    }
    .branded-header > *:not(.question-watermark) {
        position: relative;
        z-index: 1;
    }
</style>
@endpush
@endsection

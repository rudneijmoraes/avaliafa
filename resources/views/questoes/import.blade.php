@extends('layouts.app')

@section('title', 'Importar Questões — AvaliaFA')
@section('page-title', 'Importar Questões')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="max-width:820px">
            <h1 class="page-title" style="margin-bottom:6px">Importação de questões em lote</h1>
            <p class="page-subtitle" style="line-height:1.6">Importe múltiplas questões de uma vez usando arquivo CSV, XML ou ZIP (Prova Fácil / QTI). Baixe o modelo, preencha e faça o upload.</p>
        </div>
        <a href="{{ route('questoes.index') }}" class="btn btn-ghost" style="display:flex;align-items:center;gap:6px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </a>
    </div>
</div>

@if(session('import_errors'))
<div class="alert alert-error" style="margin-bottom:20px">
    <strong>Erros encontrados durante a importação:</strong>
    <ul style="margin-top:8px;padding-left:20px;display:grid;gap:4px">
        @foreach(session('import_errors') as $err)
            <li style="font-size:0.82rem">{{ $err }}</li>
        @endforeach
        @if(count(session('import_errors')) >= 50)
            <li style="font-size:0.82rem;opacity:0.7">… e possivelmente mais (máximo 50 exibidos).</li>
        @endif
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:minmax(0,1.45fr) minmax(300px,0.8fr);gap:20px;align-items:start" class="import-grid">

    {{-- Formulário principal --}}
    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Upload do arquivo</div>
                <div class="card-subtitle">Selecione o arquivo CSV, XML ou ZIP com as questões.</div>
            </div>
        </div>

        <div class="card-body">
            <form method="POST"
                  action="{{ route('questoes.import') }}"
                  enctype="multipart/form-data"
                  id="import-form"
                  x-data="importForm()"
                  @submit="submitting = true">

                @csrf

                {{-- Sistema de destino --}}
                @if(auth()->user()->isSuperAdmin())
                <div class="form-group">
                    <label class="form-label" for="client_system_id">
                        Sistema de destino <span style="color:var(--color-error)">*</span>
                    </label>
                    <select name="client_system_id" id="client_system_id" class="input @error('client_system_id') input-error @enderror" required>
                        <option value="">Selecione o sistema…</option>
                        @foreach($systems as $system)
                        <option value="{{ $system->id }}" {{ old('client_system_id') == $system->id ? 'selected' : '' }}>
                            {{ $system->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('client_system_id')
                    <div class="form-hint" style="color:var(--color-error)">{{ $message }}</div>
                    @enderror
                </div>
                @else
                <input type="hidden" name="client_system_id" value="{{ auth()->user()->client_system_id }}">
                <div class="form-group">
                    <label class="form-label">Sistema de destino</label>
                    <div class="input" style="background:var(--surface-bg);cursor:default;color:var(--text-secondary)">
                        {{ $systems->first()?->name ?? '—' }}
                    </div>
                </div>
                @endif

                {{-- Disciplina --}}
                <div class="form-group" x-data="{ showNewDiscipline: false }">
                    <label class="form-label" for="discipline_id">Disciplina</label>
                    <select name="discipline_id" id="discipline_id" class="input" @change="showNewDiscipline = ($event.target.value === 'new')">
                        <option value="">Sem disciplina (detectar do ZIP)</option>
                        @foreach($disciplines as $disc)
                        <option value="{{ $disc->id }}" {{ old('discipline_id') == $disc->id ? 'selected' : '' }}>
                            {{ $disc->name }}
                        </option>
                        @endforeach
                        <option value="new" {{ old('discipline_id') === 'new' ? 'selected' : '' }}>+ Criar nova disciplina…</option>
                    </select>
                    <div x-show="showNewDiscipline" x-cloak style="margin-top:8px">
                        <input type="text" name="discipline_name" class="input" placeholder="Nome da nova disciplina"
                               value="{{ old('discipline_name') }}" style="font-size:0.88rem">
                    </div>
                    <div class="form-hint" style="color:var(--text-muted)">Para ZIPs do Prova Fácil, a disciplina é detectada automaticamente do nome do arquivo.</div>
                </div>

                {{-- Upload --}}
                <div class="form-group">
                    <label class="form-label" for="arquivo">
                        Arquivo <span style="color:var(--color-error)">*</span>
                    </label>

                    <div x-bind:class="dragging ? 'drop-zone drop-zone-active' : 'drop-zone'"
                         @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="handleDrop($event)"
                         @click="$refs.fileInput.click()">

                        <input type="file"
                               name="arquivo"
                               id="arquivo"
                               accept=".csv,.txt,.xml,.zip"
                               x-ref="fileInput"
                               style="display:none"
                               @change="handleFile($event)"
                               required>

                        <template x-if="!fileName">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:10px;pointer-events:none">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <div>
                                    <strong style="font-size:0.9rem;color:var(--text-primary)">Clique ou arraste o arquivo aqui</strong>
                                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px">CSV, XML ou ZIP (Prova Fácil) — máximo 50 MB</div>
                                </div>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div style="display:flex;align-items:center;gap:12px;pointer-events:none">
                                <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:0.88rem;font-weight:700;color:var(--text-primary)" x-text="fileName"></div>
                                    <div style="font-size:0.76rem;color:var(--text-muted)" x-text="fileSize"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    @error('arquivo')
                    <div class="form-hint" style="color:var(--color-error)">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Ação em duplicata --}}
                <div class="form-group">
                    <label class="form-label">
                        Quando o enunciado já existir no sistema <span style="color:var(--color-error)">*</span>
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <label class="radio-card" style="cursor:pointer">
                            <input type="radio" name="acao_duplicata" value="pular"
                                   {{ old('acao_duplicata', 'pular') === 'pular' ? 'checked' : '' }}
                                   style="display:none">
                            <div class="radio-card-inner" x-bind:class="acao === 'pular' ? 'radio-card-active' : ''"
                                 @click="acao = 'pular'">
                                <div style="font-weight:700;font-size:0.86rem;color:var(--text-primary)">Pular</div>
                                <div style="font-size:0.76rem;color:var(--text-secondary);margin-top:3px">Não importa se já existe questão com o mesmo enunciado.</div>
                            </div>
                        </label>
                        <label class="radio-card" style="cursor:pointer">
                            <input type="radio" name="acao_duplicata" value="importar"
                                   {{ old('acao_duplicata') === 'importar' ? 'checked' : '' }}
                                   style="display:none">
                            <div class="radio-card-inner" x-bind:class="acao === 'importar' ? 'radio-card-active' : ''"
                                 @click="acao = 'importar'">
                                <div style="font-weight:700;font-size:0.86rem;color:var(--text-primary)">Importar mesmo assim</div>
                                <div style="font-size:0.76rem;color:var(--text-secondary);margin-top:3px">Cria nova questão mesmo que o enunciado já exista.</div>
                            </div>
                        </label>
                    </div>
                    @error('acao_duplicata')
                    <div class="form-hint" style="color:var(--color-error)">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:flex;align-items:center;gap:12px;padding-top:6px">
                    <button type="submit" class="btn btn-primary" x-bind:disabled="submitting" style="display:flex;align-items:center;gap:7px">
                        <template x-if="!submitting">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </template>
                        <template x-if="submitting">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke-dasharray="60" stroke-dashoffset="30"/></svg>
                        </template>
                        <span x-text="submitting ? 'Processando…' : 'Importar questões'"></span>
                    </button>
                    <a href="{{ route('questoes.index') }}" class="btn btn-ghost">Cancelar</a>
                </div>

            </form>
        </div>
    </section>

    {{-- Painel direito --}}
    <div style="display:grid;gap:20px">

        {{-- Template downloads --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Modelos de arquivo</div>
                    <div class="card-subtitle">Baixe o modelo no formato desejado e preencha com suas questões.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <a href="{{ route('questoes.import.template.csv') }}"
                   class="btn btn-secondary"
                   style="width:100%;justify-content:center;display:flex;align-items:center;gap:7px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Baixar modelo CSV
                </a>
                <a href="{{ route('questoes.import.template.csv.bulk') }}"
                   class="btn btn-secondary"
                   style="width:100%;justify-content:center;display:flex;align-items:center;gap:7px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Baixar modelo CSV (lote)
                </a>
                <a href="{{ route('questoes.import.guide') }}"
                   class="btn btn-ghost"
                   style="width:100%;justify-content:center;display:flex;align-items:center;gap:7px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Baixar guia rápido
                </a>
                <a href="{{ route('questoes.import.guide.advanced') }}"
                   class="btn btn-ghost"
                   style="width:100%;justify-content:center;display:flex;align-items:center;gap:7px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Baixar guia avançado
                </a>
                <a href="{{ route('questoes.import.template.xml') }}"
                   class="btn btn-secondary"
                   style="width:100%;justify-content:center;display:flex;align-items:center;gap:7px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Baixar modelo XML
                </a>
            </div>
        </section>

        {{-- Colunas CSV --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Formato CSV</div>
                    <div class="card-subtitle">Colunas esperadas no arquivo CSV.</div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:6px">
                    @foreach([
                        ['enunciado',      'Texto da questão', true],
                        ['tipo',           'multiple_choice, true_false ou multiple_answer', false],
                        ['dificuldade',    'easy, medium ou hard', false],
                        ['feedback',       'Feedback/explicação da resposta correta', false],
                        ['tags',           'Separadas por vírgula', false],
                        ['alternativa_1',  'Texto da 1ª alternativa', true],
                        ['correta_1',      '1 = correta, 0 = incorreta', true],
                        ['alternativa_2…5','Até 5 alternativas', false],
                    ] as [$col, $desc, $req])
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-bg)">
                        <code style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--color-primary-600);flex-shrink:0">{{ $col }}</code>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:0.76rem;color:var(--text-muted)">{{ $desc }}</div>
                        </div>
                        @if($req)
                        <span class="badge badge-error" style="font-size:0.65rem;flex-shrink:0">obrig.</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ZIP / Prova Fácil --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Formato ZIP (Prova Fácil / QTI)</div>
                    <div class="card-subtitle">Exportação direta do Prova Fácil.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:8px;font-size:0.78rem;color:var(--text-secondary)">
                <div style="padding:8px 10px;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-bg)">
                    Aceita ZIPs no padrão QTI v2.2 com arquivos <code style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--color-primary-600)">assessmentItem</code>.
                </div>
                <div style="padding:8px 10px;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-bg)">
                    A disciplina é detectada automaticamente do nome do ZIP (ex: <code style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--color-primary-600)">Prova_Facil_Direito.zip</code>).
                </div>
                <div style="padding:8px 10px;border-radius:8px;border:1px solid var(--surface-border);background:var(--surface-bg)">
                    Também aceita arquivos XML individuais no formato QTI v2.2.
                </div>
            </div>
        </section>

        {{-- Regras --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Regras de importação</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Mínimo de alternativas</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Cada questão precisa de pelo menos 2 alternativas, com ao menos 1 marcada como correta.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Tipos aceitos</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">multiple_choice (padrão), true_false, multiple_answer. Também aceita nomes em português.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Limite por importação</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Máximo de 50 MB por arquivo. Para bases muito grandes, divida em lotes.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Codificação</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Use UTF-8. Os modelos baixados já vêm com codificação correta.</div>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection

@push('styles')
<style>
    .drop-zone {
        border: 2px dashed var(--surface-border);
        border-radius: 14px;
        padding: 32px 20px;
        cursor: pointer;
        transition: all .18s;
        background: var(--surface-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
    }
    .drop-zone:hover,
    .drop-zone-active {
        border-color: var(--color-primary-400);
        background: rgba(37, 99, 235, 0.04);
    }
    .radio-card-inner {
        padding: 12px 14px;
        border: 2px solid var(--surface-border);
        border-radius: 12px;
        transition: all .15s;
        background: var(--surface-bg);
    }
    .radio-card-active {
        border-color: var(--color-primary-500) !important;
        background: rgba(37, 99, 235, 0.06) !important;
    }
    .radio-card:hover .radio-card-inner {
        border-color: var(--color-primary-300);
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    @media (max-width: 1100px) {
        .import-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function importForm() {
    return {
        fileName: null,
        fileSize: null,
        dragging: false,
        acao: '{{ old("acao_duplicata", "pular") }}',
        submitting: false,

        handleFile(event) {
            const file = event.target.files[0];
            if (file) this.setFile(file);
        },
        handleDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                this.$refs.fileInput.files = event.dataTransfer.files;
                this.setFile(file);
            }
        },
        setFile(file) {
            this.fileName = file.name;
            const kb = file.size / 1024;
            this.fileSize = kb > 1024
                ? (kb / 1024).toFixed(1) + ' MB'
                : kb.toFixed(0) + ' KB';
        },
    };
}
</script>
@endpush

@extends('layouts.app')

@section('title', ($system ? 'Editar Sistema' : 'Novo Sistema') . ' — AvaliaFA')
@section('page-title', $system ? 'Editar Sistema' : 'Novo Sistema')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">{{ $system ? 'Editar Sistema' : 'Cadastrar Sistema' }}</h1>
            <p class="page-subtitle">Configure integração, credenciais OAuth e parâmetros operacionais.</p>
        </div>
        <a href="{{ route('sistemas.index') }}" class="btn btn-ghost">Voltar</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:16px">
    Verifique os campos do formulário antes de salvar.
</div>
@endif

@php
    $moodle = $system->moodle_config ?? [];
    $lti = $moodle['lti'] ?? [];
    $settings = $system->settings ?? [];
@endphp

<form method="POST" action="{{ $system ? route('sistemas.update', $system) : route('sistemas.store') }}">
    @csrf
    @if($system)
    @method('PUT')
    @endif

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Dados principais</div>
                <div class="card-subtitle">Identificação e status do sistema.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Nome</label>
                <input name="name" class="input @error('name') input-error @enderror" value="{{ old('name', $system?->name) }}" placeholder="Ex.: Graduação">
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input name="slug" class="input @error('slug') input-error @enderror" value="{{ old('slug', $system?->slug) }}" placeholder="Ex.: graduacao">
                @error('slug')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Webhook URL</label>
                <input name="webhook_url" class="input @error('webhook_url') input-error @enderror" value="{{ old('webhook_url', $system?->webhook_url) }}" placeholder="https://sistema.externo/webhook">
                @error('webhook_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">IPs permitidos</label>
                <textarea name="allowed_ips" class="input @error('allowed_ips') input-error @enderror" rows="3" placeholder="Separe por vírgula, espaço ou quebra de linha">{{ old('allowed_ips', $system?->allowed_ips ? implode("\n", $system->allowed_ips) : '') }}</textarea>
                @error('allowed_ips')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary)">
                    <input type="checkbox" name="active" value="1" {{ old('active', $system?->active ?? true) ? 'checked' : '' }}>
                    Sistema ativo
                </label>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Integração Moodle</div>
                <div class="card-subtitle">Configuração por sistema para sincronização de notas.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">URL Moodle</label>
                <input name="moodle_url" class="input @error('moodle_url') input-error @enderror" value="{{ old('moodle_url', $moodle['url'] ?? '') }}" placeholder="https://moodle.anasps.edu.br">
                @error('moodle_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">URL Moodle Certificadora</label>
                <input name="moodle_certifier_url" class="input @error('moodle_certifier_url') input-error @enderror" value="{{ old('moodle_certifier_url', $moodle['certifier_url'] ?? '') }}" placeholder="https://certificadora.moodle.exemplo.br">
                @error('moodle_certifier_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Token Moodle</label>
                <input name="moodle_token" class="input @error('moodle_token') input-error @enderror" value="{{ old('moodle_token', $moodle['token'] ?? '') }}">
                @error('moodle_token')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Activity ID</label>
                <input name="moodle_activity_id" type="number" min="0" class="input @error('moodle_activity_id') input-error @enderror" value="{{ old('moodle_activity_id', $moodle['activity_id'] ?? 0) }}">
                @error('moodle_activity_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" x-data="moodleCourseSelector()" x-init="init()">
                <label class="form-label">Curso Moodle</label>
                <div style="display:flex;gap:8px">
                    <select name="moodle_course_id" class="input @error('moodle_course_id') input-error @enderror" style="flex:1" x-ref="courseSelect">
                        <option value="0">Nenhum (definir por prova)</option>
                        <template x-if="courses.length === 0 && currentId > 0">
                            <option :value="currentId" selected x-text="'ID ' + currentId + ' (clique Carregar para ver nomes)'"></option>
                        </template>
                        <template x-for="c in courses" :key="c.id">
                            <option :value="c.id" :selected="c.id == currentId" x-text="c.fullname + ' (' + c.shortname + ')'"></option>
                        </template>
                    </select>
                    <button type="button" class="btn btn-secondary" style="white-space:nowrap;padding:6px 12px;font-size:0.78rem" @click="fetchCourses()" :disabled="loading">
                        <span x-show="!loading">Carregar cursos</span>
                        <span x-show="loading">Buscando...</span>
                    </button>
                </div>
                <div x-show="error" style="color:var(--color-danger);font-size:0.78rem;margin-top:4px" x-text="error"></div>
                <div x-show="courses.length > 0" style="color:var(--color-success);font-size:0.78rem;margin-top:4px" x-text="courses.length + ' cursos encontrados'"></div>
                @error('moodle_course_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Escala</label>
                <select name="moodle_scale" class="input @error('moodle_scale') input-error @enderror">
                    @php $scale = old('moodle_scale', $moodle['scale'] ?? '0-10'); @endphp
                    <option value="0-10" {{ $scale === '0-10' ? 'selected' : '' }}>0-10</option>
                    <option value="0-100" {{ $scale === '0-100' ? 'selected' : '' }}>0-100</option>
                    <option value="percent" {{ $scale === 'percent' ? 'selected' : '' }}>Percentual</option>
                </select>
                @error('moodle_scale')<div class="form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Regras de negócio</div>
                <div class="card-subtitle">Parâmetros operacionais do sistema consumidor.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Máximo de tentativas</label>
                <input name="max_attempts" type="number" min="1" max="10" class="input @error('max_attempts') input-error @enderror" value="{{ old('max_attempts', $settings['max_attempts'] ?? 1) }}">
                @error('max_attempts')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Exibir resultado após</label>
                @php $resultAfter = old('show_results_after', $settings['show_results_after'] ?? 'submission'); @endphp
                <select name="show_results_after" class="input @error('show_results_after') input-error @enderror">
                    <option value="submission" {{ $resultAfter === 'submission' ? 'selected' : '' }}>Envio da prova</option>
                    <option value="graded" {{ $resultAfter === 'graded' ? 'selected' : '' }}>Correção</option>
                </select>
                @error('show_results_after')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" style="grid-column:1/-1;display:grid;gap:10px">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary)">
                    <input type="checkbox" name="certificate_auto_issue" value="1" {{ old('certificate_auto_issue', $settings['certificate_auto_issue'] ?? false) ? 'checked' : '' }}>
                    Emissão automática de certificados
                </label>
                @if($system)
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary)">
                    <input type="checkbox" name="regenerate_credentials" value="1">
                    Regenerar credenciais OAuth2 (client_id e client_secret)
                </label>
                @endif
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Configuracao LTI 1.3</div>
                <div class="card-subtitle">Prepare os dados do Moodle 4.1 para o AvaliaFA operar como ferramenta externa.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary)">
                    <input type="checkbox" name="lti_enabled" value="1" {{ old('lti_enabled', $lti['enabled'] ?? false) ? 'checked' : '' }}>
                    Habilitar preparacao LTI 1.3 para este sistema
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Issuer</label>
                <input name="lti_issuer" class="input @error('lti_issuer') input-error @enderror" value="{{ old('lti_issuer', $lti['issuer'] ?? '') }}" placeholder="https://moodle.seu-dominio.br">
                @error('lti_issuer')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Client ID</label>
                <input name="lti_client_id" class="input @error('lti_client_id') input-error @enderror" value="{{ old('lti_client_id', $lti['client_id'] ?? '') }}" placeholder="Client ID informado pelo Moodle">
                @error('lti_client_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Deployment ID</label>
                <input name="lti_deployment_id" class="input @error('lti_deployment_id') input-error @enderror" value="{{ old('lti_deployment_id', $lti['deployment_id'] ?? '') }}" placeholder="Deployment ID">
                @error('lti_deployment_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">OIDC Login URL</label>
                <input name="lti_platform_login_url" class="input @error('lti_platform_login_url') input-error @enderror" value="{{ old('lti_platform_login_url', $lti['platform_login_url'] ?? '') }}" placeholder="https://moodle.seu-dominio.br/mod/lti/auth.php">
                @error('lti_platform_login_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Access Token URL</label>
                <input name="lti_platform_token_url" class="input @error('lti_platform_token_url') input-error @enderror" value="{{ old('lti_platform_token_url', $lti['platform_token_url'] ?? '') }}" placeholder="https://moodle.seu-dominio.br/mod/lti/token.php">
                @error('lti_platform_token_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Keyset URL</label>
                <input name="lti_platform_keyset_url" class="input @error('lti_platform_keyset_url') input-error @enderror" value="{{ old('lti_platform_keyset_url', $lti['platform_keyset_url'] ?? '') }}" placeholder="https://moodle.seu-dominio.br/mod/lti/certs.php">
                @error('lti_platform_keyset_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-subtle);display:grid;gap:10px">
                    <div style="font-size:0.82rem;color:var(--text-secondary)">
                        Endpoints publicos do AvaliaFA para cadastro manual da ferramenta no Moodle.
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">OIDC Login Initiation URL</label>
                            <input class="input" value="{{ route('lti.login') }}" readonly onclick="this.select()">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Launch URL</label>
                            <input class="input" value="{{ route('lti.launch') }}" readonly onclick="this.select()">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Public Keyset URL</label>
                            <input class="input" value="{{ route('lti.jwks') }}" readonly onclick="this.select()">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Deep Linking URL</label>
                            <input class="input" value="{{ route('lti.deep-linking') }}" readonly onclick="this.select()">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="{{ route('sistemas.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $system ? 'Salvar alterações' : 'Cadastrar sistema' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
function moodleCourseSelector() {
    return {
        courses: [],
        loading: false,
        error: '',
        currentId: {{ old('moodle_course_id', $moodle['course_id'] ?? 0) }},
        init() {},
        async fetchCourses() {
            this.loading = true;
            this.error = '';
            const url = document.querySelector('[name="moodle_url"]')?.value || '';
            const certUrl = document.querySelector('[name="moodle_certifier_url"]')?.value || '';
            const token = document.querySelector('[name="moodle_token"]')?.value || '';
            const moodleUrl = certUrl || url;
            if (!moodleUrl || !token) {
                this.error = 'Preencha a URL e o Token do Moodle primeiro.';
                this.loading = false;
                return;
            }
            try {
                const resp = await fetch('{{ route("sistemas.moodle-courses") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ moodle_url: moodleUrl, moodle_token: token }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.courses = data.courses;
                } else {
                    this.error = data.message || 'Erro ao buscar cursos.';
                }
            } catch (e) {
                this.error = 'Falha na requisição: ' + e.message;
            }
            this.loading = false;
        },
    };
}
</script>
@endpush

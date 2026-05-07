@extends('layouts.app')
@section('title', 'Configurações Gerais — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<style>
.banner-editor-wrap{border:1px solid #D1D5DB;border-radius:10px;overflow:hidden;background:#fff}
.banner-editor-toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:8px;background:#F8FAFC;border-bottom:1px solid #E5E7EB}
.banner-editor-btn{border:1px solid #CBD5E1;background:#fff;color:#0F172A;height:30px;min-width:30px;padding:0 10px;border-radius:8px;font-size:0.78rem;font-weight:600;cursor:pointer}
.banner-editor-btn:hover{background:#F1F5F9}
.banner-editor-surface{min-height:170px;padding:12px;outline:none;font-size:.9rem;line-height:1.45;color:#0F172A}
.banner-editor-surface:empty:before{content:attr(data-placeholder);color:#94A3B8}
.banner-editor-source{display:none;border:0;border-top:1px solid #E5E7EB;border-radius:0;min-height:170px;font-family:Consolas,Monaco,monospace}
.banner-editor-wrap.source-mode .banner-editor-surface{display:none}
.banner-editor-wrap.source-mode .banner-editor-source{display:block}
</style>
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Configurações Gerais</h1>
            <p class="page-subtitle">Nome da aplicação, instituição e preferências regionais.</p>
        </div>
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:16px">
    Verifique os campos do formulário antes de salvar.
</div>
@endif

<form action="{{ route('configuracoes.geral.salvar') }}" method="POST">
    @csrf

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Identificação</div>
                <div class="card-subtitle">Dados visíveis no sistema e comunicações.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Nome da Aplicação</label>
                <input type="text" name="app_name" class="input @error('app_name') input-error @enderror"
                       value="{{ old('app_name', $settings['app_name'] ?? 'AvaliaFA') }}" required>
                @error('app_name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nome da Instituição</label>
                <input type="text" name="institution_name" class="input @error('institution_name') input-error @enderror"
                       value="{{ old('institution_name', $settings['institution_name'] ?? '') }}"
                       placeholder="Ex: Faculdade Anasps">
                @error('institution_name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">E-mail de Suporte</label>
                <input type="email" name="support_email" class="input @error('support_email') input-error @enderror"
                       value="{{ old('support_email', $settings['support_email'] ?? '') }}"
                       placeholder="suporte@faculdade.edu.br">
                @error('support_email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Telefone de Suporte</label>
                <input type="text" name="support_phone" class="input @error('support_phone') input-error @enderror"
                       value="{{ old('support_phone', $settings['support_phone'] ?? '') }}"
                       placeholder="(61) 9999-9999">
                @error('support_phone')<div class="form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Regionalização</div>
                <div class="card-subtitle">Fuso horário e idioma do sistema.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Fuso Horário</label>
                <select name="timezone" class="input">
                    <option value="America/Sao_Paulo" {{ ($settings['timezone'] ?? 'America/Sao_Paulo') === 'America/Sao_Paulo' ? 'selected' : '' }}>America/Sao_Paulo</option>
                    <option value="America/Manaus" {{ ($settings['timezone'] ?? '') === 'America/Manaus' ? 'selected' : '' }}>America/Manaus</option>
                    <option value="America/Belem" {{ ($settings['timezone'] ?? '') === 'America/Belem' ? 'selected' : '' }}>America/Belem</option>
                    <option value="America/Fortaleza" {{ ($settings['timezone'] ?? '') === 'America/Fortaleza' ? 'selected' : '' }}>America/Fortaleza</option>
                    <option value="America/Cuiaba" {{ ($settings['timezone'] ?? '') === 'America/Cuiaba' ? 'selected' : '' }}>America/Cuiaba</option>
                    <option value="America/Rio_Branco" {{ ($settings['timezone'] ?? '') === 'America/Rio_Branco' ? 'selected' : '' }}>America/Rio_Branco</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Idioma</label>
                <select name="locale" class="input">
                    <option value="pt_BR" {{ ($settings['locale'] ?? 'pt_BR') === 'pt_BR' ? 'selected' : '' }}>Português (BR)</option>
                    <option value="en" {{ ($settings['locale'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Simulados</div>
                <div class="card-subtitle">Configurações do módulo de simulados.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Banner de Inscrição (HTML permitido)</label>
                <div class="banner-editor-wrap" id="bannerEditorWrap">
                    <div class="banner-editor-toolbar">
                        <button type="button" class="banner-editor-btn" data-cmd="bold">Negrito</button>
                        <button type="button" class="banner-editor-btn" data-cmd="italic">Itálico</button>
                        <button type="button" class="banner-editor-btn" data-cmd="underline">Subl.</button>
                        <button type="button" class="banner-editor-btn" data-cmd="insertUnorderedList">Lista</button>
                        <button type="button" class="banner-editor-btn" data-cmd="insertOrderedList">1.</button>
                        <button type="button" class="banner-editor-btn" data-action="link">Link</button>
                        <button type="button" class="banner-editor-btn" data-action="image">Imagem</button>
                        <button type="button" class="banner-editor-btn" data-action="source" id="bannerSourceToggle">HTML</button>
                    </div>
                    <div id="bannerEditor" class="banner-editor-surface" contenteditable="true" data-placeholder="Digite e formate o banner aqui..."></div>
                    <textarea id="bannerEditorSource" class="input banner-editor-source @error('simulado_inscription_banner') input-error @enderror"
                        placeholder="Texto que aparece abaixo do formulário de inscrição. Deixe vazio para desativar."></textarea>
                </div>
                <textarea id="simulado_inscription_banner" name="simulado_inscription_banner" style="display:none">{{ old('simulado_inscription_banner', $settings['simulado_inscription_banner'] ?? '') }}</textarea>
                @error('simulado_inscription_banner')<div class="form-error">{{ $message }}</div>@enderror
                <div style="margin-top:6px;font-size:0.78rem;color:var(--text-secondary)">
                    Aceita HTML simples, por exemplo: &lt;strong&gt;texto&lt;/strong&gt;, &lt;a href="https://..."&gt;link&lt;/a&gt;, &lt;img src="https://..." alt="Banner"&gt;
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">URL da imagem do banner (opcional)</label>
                <input type="text" name="simulado_banner_image_url" class="input @error('simulado_banner_image_url') input-error @enderror"
                       value="{{ old('simulado_banner_image_url', $settings['simulado_banner_image_url'] ?? '') }}"
                       placeholder="https://site.com/imagem.jpg">
                @error('simulado_banner_image_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:20px;padding-top:8px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="simulado_ranking_enabled" value="1"
                        {{ old('simulado_ranking_enabled', ($settings['simulado_ranking_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}>
                    <span style="font-size:0.875rem">Habilitar ranking de alunos</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="simulado_email_notification_default" value="1"
                        {{ old('simulado_email_notification_default', ($settings['simulado_email_notification_default'] ?? 'false') === 'true') ? 'checked' : '' }}>
                    <span style="font-size:0.875rem">Notificação por e-mail ao criar/editar simulado</span>
                </label>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Salvar configurações
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const wrap = document.getElementById('bannerEditorWrap');
    const editor = document.getElementById('bannerEditor');
    const source = document.getElementById('bannerEditorSource');
    const hidden = document.getElementById('simulado_inscription_banner');
    const toggle = document.getElementById('bannerSourceToggle');
    if (!wrap || !editor || !source || !hidden || !toggle) return;

    const initial = hidden.value || '';
    editor.innerHTML = initial;
    source.value = initial;

    const syncFromEditor = () => {
        const html = editor.innerHTML;
        hidden.value = html;
        source.value = html;
    };

    const syncFromSource = () => {
        const html = source.value;
        hidden.value = html;
        editor.innerHTML = html;
    };

    editor.addEventListener('input', syncFromEditor);
    source.addEventListener('input', syncFromSource);

    wrap.querySelectorAll('.banner-editor-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            const cmd = btn.dataset.cmd;

            if (action === 'source') {
                wrap.classList.toggle('source-mode');
                if (wrap.classList.contains('source-mode')) {
                    syncFromEditor();
                    source.focus();
                } else {
                    syncFromSource();
                    editor.focus();
                }
                return;
            }

            if (wrap.classList.contains('source-mode')) return;
            editor.focus();

            if (action === 'link') {
                const url = window.prompt('Informe a URL do link (https://...)');
                if (url) document.execCommand('createLink', false, url);
                syncFromEditor();
                return;
            }

            if (action === 'image') {
                const url = window.prompt('Informe a URL da imagem (https://...)');
                if (url) document.execCommand('insertImage', false, url);
                syncFromEditor();
                return;
            }

            if (cmd) {
                document.execCommand(cmd, false, null);
                syncFromEditor();
            }
        });
    });
})();
</script>
@endpush

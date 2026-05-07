@extends('layouts.app')

@section('title', ($template ? 'Editar Template' : 'Novo Template').' — Simulados')
@section('page-title', $template ? 'Editar Template' : 'Novo Template')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $template ? 'Editar Template' : 'Novo Template' }}</h1>
        <p class="page-subtitle">Use variáveis: <code>@{{primeiro_nome}}</code> <code>@{{nome_completo}}</code> <code>@{{email}}</code> <code>@{{telefone}}</code> <code>@{{cpf}}</code> <code>@{{nota}}</code> <code>@{{percentual_acertos}}</code> <code>@{{total_acertos}}</code> <code>@{{total_erros}}</code> <code>@{{nome_simulado}}</code> <code>@{{data_realizacao}}</code></p>
    </div>
</div>

<div class="card" style="padding:20px">
    <form method="POST" action="{{ $action }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        @if(auth()->user()->isSuperAdmin())
        <div>
            <label class="form-label">Sistema</label>
            <select class="input" name="client_system_id" required>
                @foreach($systems as $system)
                <option value="{{ $system->id }}" {{ (string) old('client_system_id', data_get($template, 'client_system_id', '')) === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="form-label">Nome</label>
            <input class="input" name="name" value="{{ old('name', data_get($template, 'name', '')) }}" required>
        </div>
        <div style="grid-column:1/-1">
            <label class="form-label">Assunto</label>
            <input class="input" name="subject" value="{{ old('subject', data_get($template, 'subject', '')) }}" required>
        </div>
        <div style="grid-column:1/-1">
            <label class="form-label">HTML</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                <button class="btn btn-ghost btn-sm" type="button" id="btn-template-base">Template base</button>
                <button class="btn btn-ghost btn-sm" type="button" id="btn-template-css">Bloco CSS</button>
                <button class="btn btn-ghost btn-sm" type="button" id="btn-template-image">Imagem</button>
                <button class="btn btn-ghost btn-sm" type="button" id="btn-template-button">Botão com link</button>
                <button class="btn btn-secondary btn-sm" type="button" id="btn-preview">Preview</button>
            </div>
            <textarea class="input" rows="16" name="html_body" id="html_body" required>{{ old('html_body', data_get($template, 'html_body', '')) }}</textarea>
            <div id="preview-box" style="margin-top:10px;display:none">
                <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:6px">Pré-visualização</div>
                <iframe id="preview-frame" style="width:100%;height:420px;border:1px solid var(--surface-border);border-radius:8px;background:#fff"></iframe>
            </div>
        </div>
        <div style="grid-column:1/-1;display:grid;grid-template-columns:minmax(260px,1fr) auto;gap:8px;align-items:end">
            <div>
                <label class="form-label">E-mail para teste</label>
                <input class="input" type="email" id="test_email" value="{{ auth()->user()->email ?? '' }}" placeholder="email@dominio.com">
            </div>
            <button class="btn btn-secondary btn-sm" type="button" id="btn-send-test">Enviar teste</button>
        </div>
        <div id="test-feedback" style="grid-column:1/-1;font-size:0.8rem;color:var(--text-muted)"></div>
        <div>
            <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="active" value="1" {{ old('active', data_get($template, 'active', true)) ? 'checked' : '' }}> Ativo</label>
        </div>
        <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px">
            <a href="{{ route('simulados.templates.index') }}" class="btn btn-ghost btn-sm">Cancelar</a>
            <button class="btn btn-primary btn-sm" type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const htmlEditor = document.getElementById('html_body');
    const subjectInput = document.querySelector('input[name="subject"]');
    const testEmailInput = document.getElementById('test_email');
    const previewBox = document.getElementById('preview-box');
    const previewFrame = document.getElementById('preview-frame');
    const feedback = document.getElementById('test-feedback');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const appendAtCursor = (text) => {
        if (!htmlEditor) return;
        const start = htmlEditor.selectionStart ?? htmlEditor.value.length;
        const end = htmlEditor.selectionEnd ?? htmlEditor.value.length;
        htmlEditor.value = htmlEditor.value.substring(0, start) + text + htmlEditor.value.substring(end);
        htmlEditor.focus();
        const caret = start + text.length;
        htmlEditor.setSelectionRange(caret, caret);
    };

    document.getElementById('btn-template-base')?.addEventListener('click', () => {
        htmlEditor.value = `<html><head><style>body{font-family:Arial,sans-serif;background:#f8fafc;margin:0;padding:24px}.card{max-width:620px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px}.title{font-size:20px;font-weight:700;color:#0f172a;margin:0 0 12px}.text{font-size:14px;color:#334155;line-height:1.6}.cta{display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:700;margin-top:14px}</style></head><body><div class="card"><h1 class="title">Resultado do @{{nome_simulado}}</h1><p class="text">Olá @{{primeiro_nome}}, sua nota foi <strong>@{{nota}}</strong> com <strong>@{{percentual_acertos}}%</strong> de acertos.</p><a href="https://faculdadeanasps.com.br" class="cta">Próximos passos</a></div></body></html>`;
    });

    document.getElementById('btn-template-css')?.addEventListener('click', () => {
        appendAtCursor(`<style>\n  .destaque{color:#1d4ed8;font-weight:700}\n  .bloco{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px}\n</style>\n`);
    });

    document.getElementById('btn-template-image')?.addEventListener('click', () => {
        appendAtCursor(`<img src="https://faculdadeanasps.com.br/wp-content/uploads/2024/01/logo-faculdade-anasps.png" alt="Faculdade Anasps" style="max-width:220px;height:auto;display:block;margin:0 auto 12px;">\n`);
    });

    document.getElementById('btn-template-button')?.addEventListener('click', () => {
        appendAtCursor(`<a href="https://faculdadeanasps.com.br" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:700;">Acessar Portal</a>\n`);
    });

    document.getElementById('btn-preview')?.addEventListener('click', () => {
        const html = htmlEditor?.value || '';
        previewBox.style.display = 'block';
        previewFrame.srcdoc = html;
    });

    document.getElementById('btn-send-test')?.addEventListener('click', async () => {
        feedback.textContent = 'Enviando e-mail de teste...';
        feedback.style.color = 'var(--text-muted)';

        try {
            const response = await fetch(@json(route('simulados.templates.test-email')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    email: testEmailInput?.value || '',
                    subject: subjectInput?.value || '',
                    html_body: htmlEditor?.value || ''
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                feedback.textContent = data.message || 'Falha ao enviar e-mail de teste.';
                feedback.style.color = '#b91c1c';
                return;
            }

            feedback.textContent = data.message || 'E-mail de teste enviado com sucesso.';
            feedback.style.color = '#15803d';
        } catch (error) {
            feedback.textContent = 'Erro de comunicação ao enviar e-mail de teste.';
            feedback.style.color = '#b91c1c';
        }
    });
})();
</script>
@endpush

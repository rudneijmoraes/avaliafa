@extends('layouts.app')
@section('title', 'Backup e Manutenção — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Backup e Manutenção</h1>
            <p class="page-subtitle">Gerenciamento de backups completos, atualizações por ZIP e limpeza de cache.</p>
        </div>
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </a>
    </div>
</div>

{{-- Ações Rápidas --}}
@php
    $currentVersion = $maintenance['current_version'] ?? 'Não definida';
    $lastUpdateAt = $maintenance['last_update_at'] ?? null;
    $updateHistory = $maintenance['history'] ?? [];
@endphp

<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px">
    <div class="card">
        <div class="card-body">
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Versão atual</div>
            <div style="margin-top:8px;font-size:1.5rem;font-weight:800;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $currentVersion }}</div>
            <div style="margin-top:6px;font-size:0.8125rem;color:var(--text-secondary)">Identifica a build ativa no servidor.</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Última atualização</div>
            <div style="margin-top:8px;font-size:1.05rem;font-weight:700;color:var(--text-primary)">{{ $lastUpdateAt ? \Carbon\Carbon::parse($lastUpdateAt)->format('d/m/Y H:i') : 'Nenhuma aplicada' }}</div>
            <div style="margin-top:6px;font-size:0.8125rem;color:var(--text-secondary)">{{ $maintenance['last_update_package'] ?? 'Sem pacote registrado' }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Backup preventivo</div>
            <div style="margin-top:8px;font-size:1.05rem;font-weight:700;color:var(--text-primary)">{{ $maintenance['last_update_backup'] ?? 'Ainda não gerado' }}</div>
            <div style="margin-top:6px;font-size:0.8125rem;color:var(--text-secondary)">Cada atualização nova gera um backup antes da aplicação.</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Backup Manual</div>
                <div class="card-subtitle">Gera um ZIP com banco de dados, arquivos do sistema e artefatos importantes.</div>
            </div>
        </div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:14px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:42px;height:42px;border-radius:10px;background:var(--color-primary-50);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </div>
                <div style="font-size:0.8125rem;color:var(--text-secondary)">Inclui dump SQL, código-fonte, views, rotas, docs e arquivos persistidos em <code>storage/app</code>.</div>
            </div>
            <form action="{{ route('configuracoes.backup.executar') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">Executar Backup</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Limpar Cache</div>
                <div class="card-subtitle">Limpa cache, config e views compiladas.</div>
            </div>
        </div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:14px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:42px;height:42px;border-radius:10px;background:var(--color-warning-bg);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                </div>
                <div style="font-size:0.8125rem;color:var(--text-secondary)">Remove arquivos temporários e cache de configurações</div>
            </div>
            <form action="{{ route('configuracoes.cache.limpar') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" style="white-space:nowrap">Limpar Cache</button>
            </form>
        </div>
    </div>
</div>

{{-- Config de backup automático --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Atualizações do Sistema</div>
            <div class="card-subtitle">Envie um arquivo ZIP da nova versão para aplicar a atualização e registrar a identificação da build.</div>
        </div>
        <span class="badge badge-primary">Versão atual: {{ $currentVersion }}</span>
    </div>
    <div class="card-body">
        <form action="{{ route('configuracoes.backup.atualizar') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                <div class="form-group">
                    <label class="form-label">Nova versão</label>
                    <input type="text" name="version" class="input @error('version') input-error @enderror" value="{{ old('version') }}" placeholder="Ex.: 2026.03.19" required>
                    @error('version')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Arquivo ZIP</label>
                    <input type="file" name="package" accept=".zip,application/zip" class="input @error('package') input-error @enderror" required>
                    @error('package')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Observações da atualização</label>
                    <textarea name="notes" class="input @error('notes') input-error @enderror" rows="3" placeholder="Resumo opcional da atualização aplicada">{{ old('notes') }}</textarea>
                    @error('notes')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                <div style="font-size:0.8125rem;color:var(--text-secondary);max-width:760px;line-height:1.6">
                    O pacote pode atualizar arquivos permitidos do projeto e o sistema registra a nova versão em configurações. Antes da aplicação, um backup preventivo completo é criado automaticamente.
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Aplicar atualização
                </button>
            </div>
        </form>
    </div>
</div>

<form action="{{ route('configuracoes.backup.salvar') }}" method="POST">
    @csrf

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Backup Automático</div>
                <div class="card-subtitle">Configure backups periódicos do banco de dados.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                    <input type="checkbox" name="auto_backup" value="1"
                           {{ old('auto_backup', $settings['auto_backup'] ?? '0') == '1' ? 'checked' : '' }}>
                    Ativar backup automático
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Frequência</label>
                <select name="backup_frequency" class="input">
                    <option value="daily" {{ ($settings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' }}>Diário</option>
                    <option value="weekly" {{ ($settings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : '' }}>Semanal</option>
                    <option value="monthly" {{ ($settings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : '' }}>Mensal</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Retenção (dias)</label>
                <input type="number" name="backup_retention" class="input"
                       value="{{ old('backup_retention', $settings['backup_retention'] ?? '30') }}" min="1" max="90" required>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:20px">
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Salvar configurações
        </button>
    </div>
</form>

@if(!empty($updateHistory))
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Histórico de Atualizações</div>
            <div class="card-subtitle">Últimos pacotes aplicados nesta instalação.</div>
        </div>
        <span class="badge badge-neutral">{{ count($updateHistory) }} registro(s)</span>
    </div>
    <div class="card-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Versão</th>
                    <th>Pacote</th>
                    <th>Data</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($updateHistory as $item)
                <tr>
                    <td><span class="badge badge-primary">{{ $item['version'] ?? '-' }}</span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $item['package'] ?? '-' }}</td>
                    <td style="color:var(--text-secondary)">{{ !empty($item['applied_at']) ? \Carbon\Carbon::parse($item['applied_at'])->format('d/m/Y H:i') : '-' }}</td>
                    <td style="color:var(--text-secondary)">{{ $item['notes'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Lista de Backups --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Backups Existentes</div>
            <div class="card-subtitle">Arquivos de backup armazenados no servidor.</div>
        </div>
        <span class="badge badge-neutral">{{ count($backupFiles) }} arquivo(s)</span>
    </div>
    <div class="card-body" style="padding:0">
        @if(empty($backupFiles))
            <div style="padding:40px 24px;text-align:center">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                    <polyline points="13 2 13 9 20 9"/>
                </svg>
                <div style="font-size:0.875rem;color:var(--text-muted)">Nenhum backup encontrado</div>
                <div style="font-size:0.8125rem;color:var(--text-muted);margin-top:4px">Execute um backup para gerar o primeiro arquivo.</div>
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tamanho</th>
                        <th>Data</th>
                        <th style="text-align:right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backupFiles as $file)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-500)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:0.8125rem">{{ $file['name'] }}</span>
                            </div>
                        </td>
                        <td><span class="badge badge-primary">{{ $file['size'] }}</span></td>
                        <td style="color:var(--text-secondary)">{{ $file['date'] }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                                <a href="{{ route('configuracoes.backup.download', $file['name']) }}" class="btn btn-ghost btn-sm" title="Baixar">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Baixar
                                </a>
                                <form action="{{ route('configuracoes.backup.excluir', $file['name']) }}" method="POST" onsubmit="return confirmDelete(this, {title:'Excluir backup', message:'Excluir este backup permanentemente? Esta ação não pode ser desfeita.', confirmLabel:'Excluir'})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

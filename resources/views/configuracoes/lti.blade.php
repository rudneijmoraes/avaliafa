@extends('layouts.app')
@section('title', 'Integração LTI 1.3 — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Integração LTI 1.3</h1>
            <p class="page-subtitle">Configure a integração com Moodle via LTI 1.3 para lançamento de provas e sincronização de notas.</p>
        </div>
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </a>
    </div>
</div>

{{-- Status das Chaves RSA --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Chaves RSA</div>
            <div class="card-subtitle">As chaves RSA são necessárias para assinar e verificar tokens JWT do LTI 1.3.</div>
        </div>
    </div>
    <div class="card-body">
        @if($keysExist)
            <div style="display:flex;align-items:center;gap:10px;padding:14px;border-radius:10px;background:#ECFDF5;border:1px solid #A7F3D0;margin-bottom:12px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span style="font-size:0.875rem;color:#065F46;font-weight:600">Chaves RSA configuradas</span>
                <span style="font-size:0.8rem;color:#047857;margin-left:auto">Key ID: <code style="background:#D1FAE5;padding:2px 6px;border-radius:4px">{{ $keyId }}</code></span>
            </div>
        @else
            <div style="display:flex;align-items:center;gap:10px;padding:14px;border-radius:10px;background:#FEF3C7;border:1px solid #FDE68A;margin-bottom:12px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span style="font-size:0.875rem;color:#92400E;font-weight:600">Chaves RSA não encontradas — LTI não funcionará sem elas.</span>
            </div>
            <form action="{{ route('configuracoes.lti.gerar-chaves') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Gerar par de chaves RSA
                </button>
            </form>
        @endif
    </div>
</div>

{{-- URLs para configurar no Moodle --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">URLs do AvaliaFA para o Moodle</div>
            <div class="card-subtitle">Copie estas URLs e cole no painel de administração do Moodle ao registrar a ferramenta LTI externa.</div>
        </div>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
        <div class="form-group">
            <label class="form-label">Tool URL (Launch URL)</label>
            <input class="input" value="{{ $urls['launch'] }}" readonly onclick="this.select(); document.execCommand('copy');" style="cursor:pointer">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">URL principal da ferramenta. Clique para copiar.</div>
        </div>
        <div class="form-group">
            <label class="form-label">Initiate Login URL</label>
            <input class="input" value="{{ $urls['login'] }}" readonly onclick="this.select(); document.execCommand('copy');" style="cursor:pointer">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">OIDC login initiation. Clique para copiar.</div>
        </div>
        <div class="form-group">
            <label class="form-label">Public Keyset URL (JWKS)</label>
            <input class="input" value="{{ $urls['jwks'] }}" readonly onclick="this.select(); document.execCommand('copy');" style="cursor:pointer">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Endpoint de chaves publicas. Clique para copiar.</div>
        </div>
        <div class="form-group">
            <label class="form-label">Redirection URI(s)</label>
            <input class="input" value="{{ $urls['launch'] }}" readonly onclick="this.select(); document.execCommand('copy');" style="cursor:pointer">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Mesmo que a Tool URL. Clique para copiar.</div>
        </div>
    </div>
</div>

{{-- Passo a passo Moodle --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Como configurar no Moodle 4.x</div>
            <div class="card-subtitle">Siga este passo a passo para registrar o AvaliaFA como ferramenta LTI no Moodle.</div>
        </div>
    </div>
    <div class="card-body" style="font-size:0.875rem;color:var(--text-primary);line-height:1.8">
        <ol style="padding-left:20px;margin:0;display:grid;gap:8px">
            <li>No Moodle, vá em <strong>Administração do site → Plugins → Módulos de atividade → Ferramenta externa → Gerenciar ferramentas</strong></li>
            <li>Clique em <strong>"Configurar uma ferramenta manualmente"</strong></li>
            <li>Preencha:
                <ul style="padding-left:16px;margin-top:4px">
                    <li><strong>Nome da ferramenta:</strong> AvaliaFA</li>
                    <li><strong>Tool URL:</strong> copie a Tool URL acima</li>
                    <li><strong>Versão do LTI:</strong> LTI 1.3</li>
                    <li><strong>Initiate login URL:</strong> copie a Initiate Login URL acima</li>
                    <li><strong>Redirection URI(s):</strong> copie a Redirection URI acima</li>
                    <li><strong>Public keyset URL:</strong> copie a JWKS URL acima</li>
                </ul>
            </li>
            <li>Salve. O Moodle vai gerar o <strong>Client ID</strong></li>
            <li>Copie o <strong>Client ID</strong> e configure no <strong>AvaliaFA → Sistemas → Editar → LTI 1.3</strong> do sistema correspondente</li>
            <li>Copie também do Moodle:
                <ul style="padding-left:16px;margin-top:4px">
                    <li><strong>Issuer:</strong> URL do Moodle (ex: https://seu-moodle.com.br)</li>
                    <li><strong>OIDC Login URL:</strong> geralmente <code>https://seu-moodle.com.br/mod/lti/auth.php</code></li>
                    <li><strong>Access Token URL:</strong> geralmente <code>https://seu-moodle.com.br/mod/lti/token.php</code></li>
                    <li><strong>Keyset URL:</strong> geralmente <code>https://seu-moodle.com.br/mod/lti/certs.php</code></li>
                </ul>
            </li>
        </ol>
    </div>
</div>

{{-- Registros LTI (Plataformas Moodle) --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
            <div>
                <div class="card-title">Plataformas Moodle Conectadas</div>
                <div class="card-subtitle">Cada Moodle (Certificadora, Graduação, Pós, etc.) precisa de um registro próprio.</div>
            </div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('lti-reg-form').style.display='block'; this.style.display='none';">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo registro
            </button>
        </div>
    </div>
    <div class="card-body">

        {{-- Lista de registros existentes --}}
        @forelse($registrations as $reg)
        <div style="display:flex;align-items:center;gap:14px;padding:14px;border-radius:10px;background:var(--bg-secondary);border:1px solid var(--border-color);margin-bottom:10px">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    @if($reg->active)
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#059669"></span>
                    @else
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#D97706"></span>
                    @endif
                    <strong style="font-size:0.9375rem;color:var(--text-primary)">{{ $reg->platform_name ?: ($reg->clientSystem?->name ?? 'Sem nome') }}</strong>
                    <span style="font-size:0.75rem;color:var(--text-muted);background:var(--bg-tertiary);padding:2px 8px;border-radius:4px">{{ $reg->clientSystem?->name ?? '—' }}</span>
                </div>
                <div style="font-size:0.8125rem;color:var(--text-secondary);display:flex;gap:16px;flex-wrap:wrap">
                    <span title="Issuer">Issuer: <code style="font-size:0.75rem">{{ Str::limit($reg->issuer, 50) }}</code></span>
                    <span title="Client ID">Client ID: <code style="font-size:0.75rem">{{ $reg->client_id }}</code></span>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                <button type="button" class="btn btn-ghost" style="padding:6px 10px;font-size:0.8125rem"
                        onclick="editRegistration({{ $reg->toJson() }})">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Editar
                </button>
                <form action="{{ route('configuracoes.lti.registro.excluir', $reg) }}" method="POST" onsubmit="return confirm('Excluir este registro LTI?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost" style="padding:6px 10px;font-size:0.8125rem;color:var(--color-error)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Excluir
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:24px;color:var(--text-muted);font-size:0.875rem">
            Nenhuma plataforma Moodle registrada. Clique em <strong>Novo registro</strong> para conectar um Moodle.
        </div>
        @endforelse

        {{-- Formulário de criar/editar registro --}}
        <div id="lti-reg-form" style="display:none;margin-top:16px;padding:20px;border-radius:10px;background:var(--bg-tertiary);border:1px solid var(--border-color)">
            <div style="font-weight:700;font-size:0.9375rem;color:var(--text-primary);margin-bottom:14px" id="lti-reg-form-title">Novo registro LTI</div>
            <form action="{{ route('configuracoes.lti.registro.salvar') }}" method="POST">
                @csrf
                <input type="hidden" name="registration_id" id="reg-id" value="">

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label">Sistema vinculado</label>
                        <select name="client_system_id" id="reg-system" class="input" required>
                            <option value="">Selecione...</option>
                            @foreach($systems as $sys)
                                <option value="{{ $sys->id }}">{{ $sys->name }}</option>
                            @endforeach
                        </select>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Sistema do AvaliaFA que usará este Moodle.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nome da plataforma</label>
                        <input name="platform_name" id="reg-platform-name" class="input" placeholder="Ex.: Moodle Certificadora">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Issuer (URL do Moodle)</label>
                        <input name="issuer" id="reg-issuer" class="input" required placeholder="https://moodle.exemplo.com.br">
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">URL base do Moodle. Ex.: https://ead.faculdadeanasps.com.br</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client ID</label>
                        <input name="client_id" id="reg-client-id" class="input" required placeholder="Gerado pelo Moodle ao salvar a ferramenta">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deployment ID</label>
                        <input name="deployment_id" id="reg-deployment-id" class="input" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">OIDC Auth Login URL</label>
                        <input name="auth_login_url" id="reg-auth-login" class="input" required placeholder="https://moodle.exemplo.com.br/mod/lti/auth.php">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Token URL</label>
                        <input name="auth_token_url" id="reg-auth-token" class="input" required placeholder="https://moodle.exemplo.com.br/mod/lti/token.php">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keyset URL (JWKS do Moodle)</label>
                        <input name="keyset_url" id="reg-keyset" class="input" required placeholder="https://moodle.exemplo.com.br/mod/lti/certs.php">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;padding-top:8px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                            <input type="checkbox" name="active" id="reg-active" value="1" checked>
                            Registro ativo
                        </label>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
                    <button type="button" class="btn btn-ghost" onclick="cancelRegistrationForm()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Salvar registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Resource Links (Vinculos Moodle → Provas) --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Vinculos Atividade Moodle → Prova</div>
            <div class="card-subtitle">Cada atividade LTI do Moodle é vinculada a uma prova do AvaliaFA. Vinculos sao criados automaticamente quando o professor acessa a atividade pela primeira vez.</div>
        </div>
    </div>
    <div class="card-body">
        @forelse($resourceLinks as $rl)
        <div style="display:flex;align-items:center;gap:14px;padding:14px;border-radius:10px;background:var(--bg-secondary);border:1px solid var(--border-color);margin-bottom:10px">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#059669"></span>
                    <strong style="font-size:0.875rem;color:var(--text-primary)">{{ $rl->exam?->title ?? 'Prova removida' }}</strong>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    <span style="font-size:0.8125rem;color:var(--text-secondary)">{{ $rl->context_title ?: ($rl->context_label ?: 'Sem curso') }}</span>
                    <span style="font-size:0.75rem;color:var(--text-muted);background:var(--bg-tertiary);padding:2px 8px;border-radius:4px">{{ $rl->registration?->platform_name ?? 'Moodle' }}</span>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);display:flex;gap:12px;flex-wrap:wrap">
                    <span>Resource Link: <code>{{ Str::limit($rl->resource_link_id, 30) }}</code></span>
                    @if($rl->context_id)
                    <span>Context: <code>{{ $rl->context_id }}</code></span>
                    @endif
                    <span>Criado: {{ $rl->created_at?->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <form action="{{ route('configuracoes.lti.resource-link.excluir', $rl) }}" method="POST" onsubmit="return confirm('Excluir este vinculo? Os alunos nao poderao acessar esta prova pelo Moodle ate que um novo vinculo seja criado.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost" style="padding:6px 10px;font-size:0.8125rem;color:var(--color-error)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Excluir
                </button>
            </form>
        </div>
        @empty
        <div style="text-align:center;padding:24px;color:var(--text-muted);font-size:0.875rem">
            Nenhum vinculo criado ainda. Quando um professor acessar uma atividade LTI pelo Moodle, podera escolher a prova correspondente.
        </div>
        @endforelse
    </div>
</div>

{{-- Dica de preenchimento automático --}}
<div style="display:flex;align-items:flex-start;gap:10px;padding:14px;border-radius:10px;background:#EFF6FF;border:1px solid #BFDBFE;margin-bottom:16px;font-size:0.8125rem;color:#1E40AF;line-height:1.6">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    <div>
        <strong>Dica:</strong> Para cada Moodle diferente, você precisa repetir o processo de registro da ferramenta LTI no Moodle (usando as URLs acima)
        e depois cadastrar aqui o <strong>Client ID</strong> e as <strong>URLs</strong> que o Moodle informou.
        Cada Moodle terá seu próprio Client ID.
    </div>
</div>

{{-- Configurações gerais --}}
<form action="{{ route('configuracoes.lti.salvar') }}" method="POST">
    @csrf
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Configurações gerais LTI</div>
                <div class="card-subtitle">Parâmetros globais da integração LTI.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group" style="grid-column:1/-1">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                    <input type="checkbox" name="lti_enabled" value="1" {{ old('lti_enabled', $settings['lti_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    Habilitar integração LTI 1.3 globalmente
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Key ID (kid)</label>
                <input name="tool_key_id" class="input" value="{{ old('tool_key_id', $settings['tool_key_id'] ?? 'avaliafa-lti') }}">
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Identificador da chave usado no JWKS. Altere apenas se necessário.</div>
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

<script>
function editRegistration(reg) {
    document.getElementById('lti-reg-form').style.display = 'block';
    document.getElementById('lti-reg-form-title').textContent = 'Editar registro LTI';
    document.getElementById('reg-id').value = reg.id;
    document.getElementById('reg-system').value = reg.client_system_id || '';
    document.getElementById('reg-platform-name').value = reg.platform_name || '';
    document.getElementById('reg-issuer').value = reg.issuer || '';
    document.getElementById('reg-client-id').value = reg.client_id || '';
    document.getElementById('reg-deployment-id').value = reg.deployment_id || '';
    document.getElementById('reg-auth-login').value = reg.auth_login_url || '';
    document.getElementById('reg-auth-token').value = reg.auth_token_url || '';
    document.getElementById('reg-keyset').value = reg.keyset_url || '';
    document.getElementById('reg-active').checked = !!reg.active;
    document.getElementById('lti-reg-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function cancelRegistrationForm() {
    document.getElementById('lti-reg-form').style.display = 'none';
    document.getElementById('lti-reg-form-title').textContent = 'Novo registro LTI';
    document.getElementById('reg-id').value = '';
    document.getElementById('reg-system').value = '';
    document.getElementById('reg-platform-name').value = '';
    document.getElementById('reg-issuer').value = '';
    document.getElementById('reg-client-id').value = '';
    document.getElementById('reg-deployment-id').value = '';
    document.getElementById('reg-auth-login').value = '';
    document.getElementById('reg-auth-token').value = '';
    document.getElementById('reg-keyset').value = '';
    document.getElementById('reg-active').checked = true;
    document.querySelector('.card-header .btn-primary[onclick]').style.display = '';
}

// Auto-fill URLs based on issuer
document.getElementById('reg-issuer')?.addEventListener('blur', function() {
    const issuer = this.value.replace(/\/+$/, '');
    if (!issuer) return;

    const loginUrl = document.getElementById('reg-auth-login');
    const tokenUrl = document.getElementById('reg-auth-token');
    const keysetUrl = document.getElementById('reg-keyset');

    if (!loginUrl.value) loginUrl.value = issuer + '/mod/lti/auth.php';
    if (!tokenUrl.value) tokenUrl.value = issuer + '/mod/lti/token.php';
    if (!keysetUrl.value) keysetUrl.value = issuer + '/mod/lti/certs.php';
});
</script>
@endsection

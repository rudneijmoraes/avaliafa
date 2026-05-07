@extends('layouts.app')
@section('title', 'Configurações — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<div class="page-header">
    <h1 class="page-title">Configurações</h1>
    <p class="page-subtitle">Gerencie as configurações do sistema AvaliaFA</p>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:20px;">

    {{-- Geral --}}
    <a href="{{ route('configuracoes.geral') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:var(--color-primary-50);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Geral</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Nome, instituicao e idioma</div>
        </div>
    </a>

    {{-- E-mail e SMTP --}}
    <a href="{{ route('configuracoes.email') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#EDE9FE;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">E-mail e SMTP</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Configuracao de envio</div>
        </div>
    </a>

    {{-- Seguranca --}}
    <a href="{{ route('configuracoes.seguranca') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#FEF3C7;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Seguranca</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Senhas, sessoes e provas</div>
        </div>
    </a>

    {{-- Perfis de Acesso --}}
    <a href="{{ route('configuracoes.acessos') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#ECFEFF;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0891B2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <path d="M20 8v6"/><path d="M23 11h-6"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Perfis de Acesso</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Administrador, Criador e Comercial</div>
        </div>
    </a>

    {{-- Integração LTI 1.3 --}}
    <a href="{{ route('configuracoes.lti') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#DBEAFE;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Integração LTI 1.3</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Moodle, chaves e endpoints</div>
        </div>
    </a>

    {{-- Moodle --}}
    <a href="{{ route('configuracoes.moodle') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#FFF7ED;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EA580C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Moodle</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Sincronizacao de notas</div>
        </div>
    </a>

    {{-- Backup e Manutencao --}}
    <a href="{{ route('configuracoes.backup') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:var(--color-success-bg);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Backup e Manutencao</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Cache, backup e limpeza</div>
        </div>
    </a>

    {{-- Dicas do Professor --}}
    <a href="{{ route('conteudo.dicas.index') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#D1FAE5;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Dicas do Professor</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Gerenciar videos e dicas</div>
        </div>
    </a>

    {{-- Materiais Complementares --}}
    <a href="{{ route('conteudo.materiais.index') }}" class="card card-enter" style="text-decoration:none; cursor:pointer; transition:box-shadow 0.2s,transform 0.2s" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
        <div class="card-body" style="text-align:center; padding:32px 20px">
            <div style="width:52px;height:52px;border-radius:14px;background:#FEF9C3;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#CA8A04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <div style="font-size:0.9375rem;font-weight:700;color:var(--text-primary)">Materiais</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Biblioteca de materiais</div>
        </div>
    </a>

</div>
@endsection

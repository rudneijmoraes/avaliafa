@extends('layouts.app')

@section('title', 'Materiais de Estudo')
@section('page-title', 'Materiais de Estudo')

@section('content')
<div class="page-header">
    <h1 class="page-title">Materiais de estudo</h1>
    <p class="page-subtitle">Acesse os arquivos e documentos disponibilizados pelos professores para complementar seus estudos.</p>
</div>

@if($materials->isEmpty())
<div style="padding:48px 24px;text-align:center;color:var(--text-secondary)">
    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;display:block;opacity:0.4">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    <p style="font-size:1rem;font-weight:600;margin-bottom:4px;color:var(--text-primary)">Nenhum material disponível no momento.</p>
    <p style="font-size:0.875rem">Os professores publicarão materiais de estudo aqui em breve.</p>
</div>
@else
<style>
.materials-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
@media (max-width:900px) { .materials-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:600px) { .materials-grid { grid-template-columns:1fr; } }
</style>

<div class="materials-grid">
    @foreach($materials as $material)
    <article class="card" style="overflow:hidden;padding:0;display:flex;flex-direction:column">
        {{-- Capa --}}
        @if($material->cover_url)
        <div style="aspect-ratio:4/3;overflow:hidden">
            <img src="{{ $material->cover_url }}"
                 alt="{{ $material->title }}"
                 style="width:100%;height:100%;object-fit:cover;display:block">
        </div>
        @else
        <div style="aspect-ratio:4/3;background:linear-gradient(135deg,#1D4ED8,#7C3AED);display:flex;align-items:center;justify-content:center">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
        </div>
        @endif

        {{-- Corpo do card --}}
        <div class="card-body" style="flex:1;display:flex;flex-direction:column;gap:10px">
            <h3 style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin:0;line-height:1.4">
                {{ $material->title }}
            </h3>

            @if($material->description)
            <p style="font-size:0.82rem;color:var(--text-secondary);margin:0;line-height:1.5;
                       display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                {!! strip_tags($material->description) !!}
            </p>
            @endif

            <div style="margin-top:auto;padding-top:8px">
                <a href="{{ $material->file_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
                          background:var(--color-primary-600,#2563EB);color:#fff;border-radius:8px;
                          font-size:0.82rem;font-weight:700;text-decoration:none;
                          transition:background 0.15s ease"
                   onmouseover="this.style.background='var(--color-primary-700,#1D4ED8)'"
                   onmouseout="this.style.background='var(--color-primary-600,#2563EB)'">
                    Abrir
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                </a>
            </div>
        </div>
    </article>
    @endforeach
</div>

{{-- Paginação --}}
@if($materials->hasPages())
<div style="margin-top:32px">
    {{ $materials->links() }}
</div>
@endif
@endif
@endsection

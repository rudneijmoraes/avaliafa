@extends('layouts.app')

@section('title', 'Dicas dos Professores')
@section('page-title', 'Dicas dos Professores')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dicas dos professores</h1>
    <p class="page-subtitle">Vídeos e orientações compartilhados pelos professores para apoiar seus estudos.</p>
</div>

@if($tips->isEmpty())
<div style="padding:48px 24px;text-align:center;color:var(--text-secondary)">
    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;display:block;opacity:0.4">
        <polygon points="23 7 16 12 23 17 23 7"/>
        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
    </svg>
    <p style="font-size:1rem;font-weight:600;margin-bottom:4px;color:var(--text-primary)">Nenhuma dica disponível no momento.</p>
    <p style="font-size:0.875rem">Verifique novamente em breve. Os professores publicarão dicas em vídeo aqui.</p>
</div>
@else
<style>
.tips-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; }
@media (max-width:768px) { .tips-grid { grid-template-columns:1fr; } }
</style>

<div class="tips-grid">
    @foreach($tips as $tip)
    <article class="card" style="overflow:hidden;padding:0">
        {{-- Área do vídeo --}}
        @if($tip->embed_url)
        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px 8px 0 0">
            <iframe src="{{ $tip->embed_url }}"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:none"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin">
            </iframe>
        </div>
        @else
        <div style="aspect-ratio:16/9;background:linear-gradient(135deg,#1e3a5f,#374151);display:flex;align-items:center;justify-content:center;border-radius:8px 8px 0 0">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.35)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="23 7 16 12 23 17 23 7"/>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
            </svg>
        </div>
        @endif

        {{-- Corpo do card --}}
        <div class="card-body">
            @if($tip->simulado)
            <div style="font-size:0.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--color-primary-600);margin-bottom:4px">
                {{ $tip->simulado->name }}
            </div>
            @endif
            <h3 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin:0 0 8px">
                {{ $tip->title }}
            </h3>

            @if($tip->description)
            <div style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:14px;line-height:1.6">
                {!! $tip->description !!}
            </div>
            @endif

            <div style="font-size:0.78rem;color:var(--text-muted)">
                {{ $tip->created_at->format('d/m/Y') }}
            </div>
        </div>
    </article>
    @endforeach
</div>
@endif
@endsection

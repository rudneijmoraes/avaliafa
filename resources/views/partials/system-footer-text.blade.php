@php
    $rawVersion = trim((string) \App\Models\Setting::get('maintenance', 'current_version', '1.0.0'));
    $normalizedVersion = $rawVersion !== '' && preg_match('/^[Vv]/', $rawVersion)
        ? $rawVersion
        : 'V'.$rawVersion;
@endphp
Sistema de avaliações - AvaliaFA | Núcleo de Tecnologia da Faculdade Anasps | {{ $normalizedVersion }}

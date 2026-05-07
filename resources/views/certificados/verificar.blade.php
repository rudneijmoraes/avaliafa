<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Certificado — AvaliaFA</title>
    <style>
        body {
            margin: 0;
            font-family: "Inter", Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 680px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .head {
            padding: 18px 22px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .body {
            padding: 20px 22px;
            display: grid;
            gap: 12px;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            width: fit-content;
        }
        .ok {
            color: #059669;
            background: #d1fae5;
        }
        .bad {
            color: #dc2626;
            background: #fee2e2;
        }
        .row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .label {
            color: #64748b;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }
        .value {
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 600;
            word-break: break-word;
        }
        @media (max-width: 700px) {
            .row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">
            <h1 class="title">Verificação Pública de Certificado</h1>
        </div>
        <div class="body">
            @if($certificate)
                <span class="status {{ $isValid ? 'ok' : 'bad' }}">
                    {{ $isValid ? 'Certificado válido' : 'Certificado inválido/revogado' }}
                </span>

                <div class="row">
                    <div class="label">Código</div>
                    <div class="value">{{ $certificate->code }}</div>
                </div>
                <div class="row">
                    <div class="label">Estudante</div>
                    <div class="value">{{ $certificate->student?->name ?? 'Não encontrado' }}</div>
                </div>
                <div class="row">
                    <div class="label">CPF</div>
                    <div class="value">{{ $certificate->student?->cpf ?? 'Não informado' }}</div>
                </div>
                <div class="row">
                    <div class="label">Prova</div>
                    <div class="value">{{ $certificate->exam?->title ?? 'Não encontrada' }}</div>
                </div>
                <div class="row">
                    <div class="label">Nota final</div>
                    <div class="value">{{ number_format((float) $certificate->final_score, 2, ',', '.') }}</div>
                </div>
                <div class="row">
                    <div class="label">Emitido em</div>
                    <div class="value">{{ $certificate->issued_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
                <div class="row">
                    <div class="label">Revogado em</div>
                    <div class="value">{{ $certificate->revoked_at?->format('d/m/Y H:i') ?? 'Não' }}</div>
                </div>
            @else
                <span class="status bad">Certificado não encontrado</span>
                <div class="value">O código informado não corresponde a nenhum certificado válido no sistema.</div>
            @endif
        </div>
    </div>
</div>
</body>
</html>

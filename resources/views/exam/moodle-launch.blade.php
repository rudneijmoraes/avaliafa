@extends('layouts.exam')

@section('exam-title', 'Entrar na avaliação')

@section('body')
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:radial-gradient(circle at top, rgba(37,99,235,0.10), transparent 42%), var(--exam-bg);">
    <div style="width:min(100%, 560px);background:#fff;border:1px solid var(--exam-border);border-radius:24px;box-shadow:0 24px 60px rgba(15,23,42,0.12);overflow:hidden">
        <div style="padding:24px 24px 18px;border-bottom:1px solid var(--exam-border);background:linear-gradient(135deg, rgba(37,99,235,0.08), rgba(37,99,235,0.02));">
            <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(37,99,235,0.12);color:#1D4ED8;font-size:0.76rem;font-weight:700;margin-bottom:12px">
                Moodle + AvaliaFA
            </div>
            <h1 style="margin:0 0 6px;font-size:1.5rem;line-height:1.2;color:var(--text-primary)">Entrar na avaliação</h1>
            <p style="margin:0;font-size:0.92rem;line-height:1.6;color:var(--text-secondary)">
                {{ $exam->title }}
            </p>
        </div>

        <div style="padding:24px">
            <p style="margin:0 0 18px;font-size:0.9rem;line-height:1.6;color:var(--text-secondary)">
                Informe seu CPF para localizar sua sessão liberada.
            </p>

            @if($errors->any())
            <div style="margin-bottom:16px;padding:12px 14px;border:1px solid rgba(239,68,68,0.2);border-radius:12px;background:rgba(239,68,68,0.08);font-size:0.84rem;color:#B91C1C">
                {{ $errors->first('student_reference') }}
            </div>
            @endif

            <form method="POST" action="{{ route('exam.moodle-launch.start', $exam) }}" style="display:grid;gap:14px">
                @csrf
                <div>
                    <label for="student_reference" style="display:block;margin-bottom:6px;font-size:0.82rem;font-weight:700;color:var(--text-primary)">CPF do estudante</label>
                    <input
                        id="student_reference"
                        name="student_reference"
                        type="text"
                        value="{{ old('student_reference') }}"
                        inputmode="numeric"
                        autocomplete="off"
                        class="input"
                        style="width:100%;height:46px;padding:0 14px;border:1px solid var(--exam-border);border-radius:12px;font-size:0.95rem"
                        placeholder="000.000.000-00"
                        required
                    >
                </div>

                <button type="submit" style="height:46px;border:none;border-radius:12px;background:linear-gradient(135deg, #1D4ED8, #2563EB);color:#fff;font-size:0.92rem;font-weight:700;cursor:pointer;box-shadow:0 12px 24px rgba(37,99,235,0.18)">
                    Localizar e iniciar prova
                </button>
            </form>

            <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--exam-border);font-size:0.78rem;line-height:1.6;color:var(--text-muted)">
                Se a turma já foi liberada no AvaliaFA, sua sessão será aberta imediatamente.
            </div>
        </div>
    </div>
</div>
@endsection

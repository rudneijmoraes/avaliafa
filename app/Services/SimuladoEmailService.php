<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SimuladoEmailService
{
    public function sendResultEmail(SimuladoRegistration $registration): bool
    {
        $registration->refresh();

        if ($registration->email_sent_at || $registration->status === 'email_sent') {
            return false;
        }

        $registration->loadMissing([
            'simulado.template',
            'simulado.exam',
            'participant',
            'examSession',
        ]);

        $simulado = $registration->simulado;
        $participant = $registration->participant;
        $template = $simulado?->template;

        if (! $simulado || ! $participant || ! $participant->email || ! $template || ! $template->active) {
            return false;
        }

        $subject = $this->replaceVariables($template->subject, $registration);
        $html    = $this->replaceVariables($template->html_body, $registration);

        try {
            Mail::html($html, function ($message) use ($participant, $subject) {
                $message->to($participant->email)->subject($subject);
            });

            EmailLog::record(
                type: 'resultado',
                simulado: $simulado,
                recipientEmail: $participant->email,
                recipientName: trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                subject: $subject,
            );

            $registration->update([
                'status'        => 'email_sent',
                'email_sent_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::warning("Falha ao enviar e-mail de resultado para {$participant->email}: ".$e->getMessage());

            EmailLog::record(
                type: 'resultado',
                simulado: $simulado,
                recipientEmail: $participant->email,
                recipientName: trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                subject: $subject,
                error: $e->getMessage(),
            );

            return false;
        }
    }

    public function sendWelcomeEmail(SimuladoParticipant $participant, int $clientSystemId): bool
    {
        if (! $participant->email) {
            return false;
        }

        $simulados = \App\Models\Simulado::query()
            ->where('client_system_id', $clientSystemId)
            ->where('status', 'active')
            ->whereHas('exam.questions')
            ->orderBy('hub_order')
            ->orderByDesc('id')
            ->get(['id', 'name', 'description', 'slug']);

        $firstName = $participant->first_name ?? '';
        $simuladosList = '';

        foreach ($simulados as $simulado) {
            $desc = $simulado->description ? '<p style="margin:2px 0 0;font-size:13px;color:#475569">' . e($simulado->description) . '</p>' : '';
            $simuladosList .= '
                <tr>
                    <td style="padding:10px 14px;border-bottom:1px solid #E2E8F0">
                        <strong style="color:#1E3A8A;font-size:14px">' . e($simulado->name) . '</strong>
                        ' . $desc . '
                    </td>
                </tr>';
        }

        if ($simuladosList === '') {
            $simuladosList = '<tr><td style="padding:10px 14px;color:#64748B;font-size:13px">Nenhum simulado disponível no momento.</td></tr>';
        }

        $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:Inter,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:32px 16px">
<tr><td align="center">
<table width="100%" style="max-width:560px;background:#FFFFFF;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08)">
    <tr>
        <td style="background:linear-gradient(135deg,#1D4ED8,#2563EB);padding:32px 32px 24px;text-align:center">
            <h1 style="margin:0;color:#FFFFFF;font-size:22px;font-weight:800">Bem-vindo(a) aos Simulados!</h1>
            <p style="margin:8px 0 0;color:#BFDBFE;font-size:14px">Faculdade Anasps — Plataforma de Simulados</p>
        </td>
    </tr>
    <tr>
        <td style="padding:28px 32px">
            <p style="margin:0 0 16px;font-size:15px;color:#1E293B">Olá, <strong>' . e($firstName) . '</strong>!</p>
            <p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.6">
                Seu cadastro foi realizado com sucesso. Abaixo estão os simulados disponíveis para você neste ciclo:
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;margin-bottom:24px">
                ' . $simuladosList . '
            </table>
            <p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.6">
                Acesse sua área de simulados para iniciar quando estiver pronto(a).
            </p>
            <p style="margin:0 0 24px;text-align:center">
                <a href="' . route('simulados.minha-area') . '" style="display:inline-block;background:linear-gradient(135deg,#1D4ED8,#2563EB);color:#FFFFFF;font-weight:800;font-size:15px;padding:14px 32px;border-radius:12px;text-decoration:none">
                    Acessar meus simulados
                </a>
            </p>
            <p style="margin:0;font-size:13px;color:#94A3B8">
                Em caso de dúvidas, entre em contato com a equipe da Faculdade Anasps.
            </p>
        </td>
    </tr>
    <tr>
        <td style="background:#F8FAFF;padding:16px 32px;text-align:center;border-top:1px solid #E2E8F0">
            <p style="margin:0;font-size:12px;color:#94A3B8">AvaliaFA · Faculdade Anasps</p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>';

        try {
            Mail::html($html, function ($message) use ($participant, $firstName) {
                $message->to($participant->email)
                    ->subject('Bem-vindo(a) aos Simulados da Anasps, ' . $firstName . '!');
            });

            EmailLog::record(
                type: 'boas_vindas',
                simulado: null,
                recipientEmail: $participant->email,
                recipientName: trim(($participant->first_name ?? '') . ' ' . ($participant->last_name ?? '')),
                subject: 'Bem-vindo(a) aos Simulados da Anasps, ' . $firstName . '!',
            );

            return true;
        } catch (\Exception $e) {
            \Log::warning("Falha ao enviar e-mail de boas-vindas para {$participant->email}: " . $e->getMessage());

            return false;
        }
    }

    private function replaceVariables(string $content, SimuladoRegistration $registration): string
    {
        $participant = $registration->participant;
        $session = $registration->examSession;
        $simulado = $registration->simulado;
        $fullName = trim(($participant->first_name ?? '').' '.($participant->last_name ?? ''));

        $totalCorrect = (int) ($registration->total_correct ?? 0);
        $totalWrong = (int) ($registration->total_wrong ?? 0);

        $replacements = [
            '{{primeiro_nome}}' => (string) ($participant->first_name ?? ''),
            '{{nome_completo}}' => $fullName,
            '{{email}}' => (string) ($participant->email ?? ''),
            '{{telefone}}' => (string) ($participant->phone ?? ''),
            '{{cpf}}' => (string) ($participant->cpf ?? ''),
            '{{nota}}' => number_format((float) ($registration->final_score ?? 0), 2, ',', '.'),
            '{{percentual_acertos}}' => number_format((float) ($registration->percentage_correct ?? 0), 2, ',', '.'),
            '{{total_acertos}}' => (string) $totalCorrect,
            '{{total_erros}}' => (string) $totalWrong,
            '{{nome_simulado}}' => (string) ($simulado->name ?? ''),
            '{{data_realizacao}}' => optional($session?->submitted_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function sendNewSimuladoNotification(Simulado $simulado): int
    {
        return $this->sendAvailabilityNotification($simulado, false);
    }

    public function sendAvailabilityNotification(Simulado $simulado, bool $onlyPending = true): int
    {
        $simulado->loadMissing('template');

        $template = $simulado->template;

        if (! $template || ! $template->active) {
            return 0;
        }

        $registrations = SimuladoRegistration::query()
            ->with('participant')
            ->where('simulado_id', $simulado->id)
            ->when($onlyPending, fn ($query) => $query->whereNull('email_sent_at'))
            ->whereIn('status', ['registered', 'in_progress', 'completed'])
            ->orderBy('id')
            ->get();

        $sentCount = 0;
        $sentParticipantIds = [];

        foreach ($registrations as $registration) {
            $participant = $registration->participant;

            if (! $participant || ! $participant->email) {
                continue;
            }

            if (in_array($participant->id, $sentParticipantIds, true)) {
                continue;
            }

            try {
                $subject = $this->replaceVariablesForNewSimulado($template->subject, $simulado, $participant);
                $html    = $this->replaceVariablesForNewSimulado($template->html_body, $simulado, $participant);

                \Illuminate\Support\Facades\Mail::html($html, function ($message) use ($participant, $subject) {
                    $message->to($participant->email)->subject($subject);
                });

                EmailLog::record(
                    type: 'disponibilidade',
                    simulado: $simulado,
                    recipientEmail: $participant->email,
                    recipientName: trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                    subject: $subject,
                );

                $sentCount++;
                $sentParticipantIds[] = $participant->id;
            } catch (\Exception $e) {
                \Log::warning("Falha ao enviar notificação de simulado para {$participant->email}: ".$e->getMessage());

                EmailLog::record(
                    type: 'disponibilidade',
                    simulado: $simulado,
                    recipientEmail: $participant->email,
                    recipientName: trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                    subject: $subject ?? null,
                    error: $e->getMessage(),
                );
            }
        }

        return $sentCount;
    }

    /**
     * Envia e-mail de divulgação para TODOS os alunos do sistema (users com role=student)
     * do mesmo client_system_id do simulado. Não depende de SimuladoRegistration.
     *
     * @return array{sent:int, failed:int, total:int}
     */
    public function broadcastToAllStudents(Simulado $simulado): array
    {
        $simulado->loadMissing('template');
        $template = $simulado->template;

        if (! $template || ! $template->active) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $query = User::query()
            ->where('role', 'student')
            ->whereNotNull('email');

        // Escopa pelo client_system_id quando não for super_admin
        if ($simulado->client_system_id) {
            $query->where('client_system_id', $simulado->client_system_id);
        }

        $users = $query->get(['id', 'first_name', 'last_name', 'email', 'cpf', 'phone']);

        $sent   = 0;
        $failed = 0;
        $total  = $users->count();

        foreach ($users as $user) {
            try {
                $subject = $this->replaceVariablesForBroadcast($template->subject, $simulado, $user);
                $html    = $this->replaceVariablesForBroadcast($template->html_body, $simulado, $user);

                Mail::html($html, function ($message) use ($user, $subject) {
                    $message->to($user->email)->subject($subject);
                });

                EmailLog::record(
                    type: 'broadcast',
                    simulado: $simulado,
                    recipientEmail: $user->email,
                    recipientName: trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                    subject: $subject,
                );

                $sent++;
            } catch (\Exception $e) {
                \Log::warning("Broadcast simulado #{$simulado->id} falhou para {$user->email}: ".$e->getMessage());

                EmailLog::record(
                    type: 'broadcast',
                    simulado: $simulado,
                    recipientEmail: $user->email,
                    recipientName: trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                    subject: null,
                    error: $e->getMessage(),
                );

                $failed++;
            }
        }

        $simulado->update(['broadcast_sent_at' => now()]);

        return compact('sent', 'failed', 'total');
    }

    private function replaceVariablesForBroadcast(string $content, Simulado $simulado, User $user): string
    {
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        $replacements = [
            '{{primeiro_nome}}'     => (string) ($user->first_name ?? ''),
            '{{nome_completo}}'      => $fullName,
            '{{email}}'              => (string) ($user->email ?? ''),
            '{{telefone}}'           => (string) ($user->phone ?? ''),
            '{{cpf}}'                => (string) ($user->cpf ?? ''),
            '{{nome_simulado}}'      => (string) ($simulado->name ?? ''),
            '{{descricao_simulado}}' => (string) ($simulado->description ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function countBroadcastStudents(Simulado $simulado): int
    {
        $query = User::query()
            ->where('role', 'student')
            ->whereNotNull('email');

        if ($simulado->client_system_id) {
            $query->where('client_system_id', $simulado->client_system_id);
        }

        return $query->count();
    }

    public function sendToParticipant(Simulado $simulado, SimuladoParticipant $participant, SimuladoRegistration $registration): bool
    {
        $simulado->loadMissing('template');
        $template = $simulado->template;

        if (! $template || ! $template->active || ! $participant->email) {
            return false;
        }

        try {
            $subject = $this->replaceVariablesForNewSimulado($template->subject, $simulado, $participant);
            $html    = $this->replaceVariablesForNewSimulado($template->html_body, $simulado, $participant);

            \Illuminate\Support\Facades\Mail::html($html, function ($message) use ($participant, $subject) {
                $message->to($participant->email)->subject($subject);
            });

            EmailLog::record(
                type: 'disponibilidade',
                simulado: $simulado,
                recipientEmail: $participant->email,
                recipientName: trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                subject: $subject,
            );

            return true;
        } catch (\Exception $e) {
            \Log::warning("Falha ao enviar e-mail para {$participant->email}: ".$e->getMessage());

            EmailLog::record(
                type: 'disponibilidade',
                simulado: $simulado,
                recipientEmail: $participant->email,
                recipientName: trim(($participant->first_name ?? '').' '.($participant->last_name ?? '')),
                subject: null,
                error: $e->getMessage(),
            );

            return false;
        }
    }

    private function replaceVariablesForNewSimulado(string $content, Simulado $simulado, SimuladoParticipant $participant): string
    {
        $fullName = trim(($participant->first_name ?? '').' '.($participant->last_name ?? ''));

        $replacements = [
            '{{primeiro_nome}}' => (string) ($participant->first_name ?? ''),
            '{{nome_completo}}' => $fullName,
            '{{email}}' => (string) ($participant->email ?? ''),
            '{{telefone}}' => (string) ($participant->phone ?? ''),
            '{{cpf}}' => (string) ($participant->cpf ?? ''),
            '{{nome_simulado}}' => (string) ($simulado->name ?? ''),
            '{{descricao_simulado}}' => (string) ($simulado->description ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}

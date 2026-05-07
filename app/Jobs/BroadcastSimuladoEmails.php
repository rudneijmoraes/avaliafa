<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\Simulado;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BroadcastSimuladoEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600; // 10 minutos — necessário para bases grandes

    public function __construct(
        public readonly int $simuladoId,
    ) {}

    public function handle(): void
    {
        $simulado = Simulado::with('template')->find($this->simuladoId);

        if (! $simulado) {
            Log::warning("BroadcastSimuladoEmails: simulado #{$this->simuladoId} não encontrado.");
            return;
        }

        $template = $simulado->template;

        if (! $template || ! $template->active) {
            Log::warning("BroadcastSimuladoEmails: simulado #{$simulado->id} sem template ativo.");
            return;
        }

        $query = User::query()
            ->where('role', 'student')
            ->whereNotNull('email');

        if ($simulado->client_system_id) {
            $query->where('client_system_id', $simulado->client_system_id);
        }

        $sent   = 0;
        $failed = 0;

        $query->select(['id', 'first_name', 'last_name', 'email', 'cpf', 'phone'])
              ->chunk(50, function ($users) use ($template, $simulado, &$sent, &$failed) {
                  foreach ($users as $user) {
                      try {
                          $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

                          $replacements = [
                              '{{primeiro_nome}}'      => (string) ($user->first_name ?? ''),
                              '{{nome_completo}}'      => $fullName,
                              '{{email}}'              => (string) ($user->email ?? ''),
                              '{{telefone}}'           => (string) ($user->phone ?? ''),
                              '{{cpf}}'                => (string) ($user->cpf ?? ''),
                              '{{nome_simulado}}'      => (string) ($simulado->name ?? ''),
                              '{{descricao_simulado}}' => (string) ($simulado->description ?? ''),
                          ];

                          $subject = str_replace(array_keys($replacements), array_values($replacements), $template->subject);
                          $html    = str_replace(array_keys($replacements), array_values($replacements), $template->html_body);

                          Mail::html($html, fn ($m) => $m->to($user->email)->subject($subject));

                          EmailLog::record(
                              type: 'broadcast',
                              simulado: $simulado,
                              recipientEmail: $user->email,
                              recipientName: $fullName,
                              subject: $subject,
                          );

                          $sent++;
                      } catch (\Exception $e) {
                          Log::warning("Broadcast simulado #{$simulado->id} falhou para {$user->email}: ".$e->getMessage());

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

                  // Pequena pausa entre chunks para não sobrecarregar o servidor de e-mail
                  usleep(500_000); // 0.5s
              });

        $simulado->update([
            'broadcast_sent_at'      => now(),
            'broadcast_total_sent'   => $sent,
            'broadcast_total_target' => $sent + $failed,
        ]);

        Log::info("Broadcast simulado #{$simulado->id} concluído: {$sent} enviados, {$failed} falhas.");
    }
}

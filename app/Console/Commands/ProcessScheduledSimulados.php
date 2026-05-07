<?php

namespace App\Console\Commands;

use App\Services\SimuladoSchedulingService;
use Illuminate\Console\Command;

class ProcessScheduledSimulados extends Command
{
    protected $signature = 'simulados:process-schedule';

    protected $description = 'Ativa e inativa simulados programados conforme a janela de data e hora.';

    public function handle(SimuladoSchedulingService $service): int
    {
        $result = $service->process();

        $this->info(sprintf(
            'Simulados processados. Ativados: %d | Inativados: %d | E-mails enviados: %d',
            $result['activated'],
            $result['deactivated'],
            $result['emails_sent'],
        ));

        return self::SUCCESS;
    }
}

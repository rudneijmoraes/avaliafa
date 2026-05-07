<?php

namespace App\Services;

use App\Models\SimuladoParticipant;
use Illuminate\Support\Facades\DB;

class SimuladoRankingService
{
    public function getRanking(int $limit = 50, ?int $clientSystemId = null): array
    {
        $totalQuestionsSql = 'SUM(COALESCE(simulado_registrations.total_correct, 0) + COALESCE(simulado_registrations.total_wrong, 0))';
        $totalCorrectSql = 'SUM(COALESCE(simulado_registrations.total_correct, 0))';
        $percentageSql = "ROUND(IFNULL(($totalCorrectSql * 100.0) / NULLIF($totalQuestionsSql, 0), 0))";
        $scoreSql = 'SUM(COALESCE(simulado_registrations.final_score, exam_sessions.final_score, simulado_registrations.raw_score, exam_sessions.raw_score, 0))';

        $query = SimuladoParticipant::query()
            ->select([
                'simulado_participants.id',
                'simulado_participants.first_name',
                'simulado_participants.last_name',
                'simulado_participants.cpf',
                'simulado_participants.email',
                'simulado_participants.phone',
                DB::raw('COUNT(exam_sessions.id) as total_simulados'),
                DB::raw("$totalCorrectSql as total_correct"),
                DB::raw("$totalQuestionsSql as total_questions"),
                DB::raw("$percentageSql as percentage_correct"),
                DB::raw("$scoreSql as score"),
            ])
            ->join('simulado_registrations', 'simulado_participants.id', '=', 'simulado_registrations.participant_id')
            ->join('exam_sessions', 'simulado_registrations.exam_session_id', '=', 'exam_sessions.id')
            ->join('simulados', 'simulado_registrations.simulado_id', '=', 'simulados.id')
            ->where('exam_sessions.is_simulation', true)
            ->whereNotNull('simulado_registrations.completed_at')
            ->groupBy([
                'simulado_participants.id',
                'simulado_participants.first_name',
                'simulado_participants.last_name',
                'simulado_participants.cpf',
                'simulado_participants.email',
                'simulado_participants.phone',
            ])
            ->orderByDesc('score')
            ->orderByDesc('total_simulados')
            ->limit($limit);

        if ($clientSystemId) {
            $query->where('simulado_participants.client_system_id', $clientSystemId);
        }

        $results = $query->get()->toArray();

        foreach ($results as &$row) {
            $row['cpf'] = $this->formatCpf($row['cpf'] ?? '');
            $row['total_simulados'] = (int) ($row['total_simulados'] ?? 0);
            $row['total_correct'] = (int) round((float) ($row['total_correct'] ?? 0));
            $row['total_questions'] = (int) round((float) ($row['total_questions'] ?? 0));
            $row['percentage_correct'] = (int) round((float) ($row['percentage_correct'] ?? 0));
            $row['score'] = round((float) ($row['score'] ?? 0), 2);
        }

        return $results;
    }

    public function formatCpf(string $cpf): string
    {
        $digits = preg_replace('/\D/', '', $cpf);

        if (strlen($digits) !== 11) {
            return $cpf;
        }

        return sprintf('%s.***.***-%s',
            substr($digits, 0, 3),
            substr($digits, -2)
        );
    }

    public function isRankingEnabled(): bool
    {
        return (bool) \App\Models\Setting::get('simulados', 'ranking_enabled', false);
    }
}

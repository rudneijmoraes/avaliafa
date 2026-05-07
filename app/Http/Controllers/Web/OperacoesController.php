<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\MoodleSyncLog;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperacoesController extends Controller
{
    private function currentUser(): User
    {
        /** @var User */
        return Auth::user();
    }

    public function index(Request $request)
    {
        $user = $this->currentUser();
        $isSuperAdmin = $user->isSuperAdmin();
        $hours = in_array((int) $request->integer('hours', 24), [6, 24, 72], true) ? (int) $request->integer('hours', 24) : 24;
        $since = now()->subHours($hours);
        $systemId = $isSuperAdmin ? null : $user->client_system_id;

        $webhookBase = WebhookLog::query()
            ->where('created_at', '>=', $since)
            ->when($systemId, fn ($q) => $q->where('client_system_id', $systemId));

        $moodleBase = MoodleSyncLog::query()
            ->where('created_at', '>=', $since)
            ->when($systemId, fn ($q) => $q->where('client_system_id', $systemId));

        $webhookTotal = (clone $webhookBase)->count();
        $webhookDelivered = (clone $webhookBase)->where('status', 'delivered')->count();
        $webhookFailed = (clone $webhookBase)->whereIn('status', ['failed', 'retrying'])->count();

        $moodleTotal = (clone $moodleBase)->count();
        $moodleSuccess = (clone $moodleBase)->where('status', 'success')->count();
        $moodleFailed = (clone $moodleBase)->whereIn('status', ['failed', 'retrying'])->count();

        $stats = [
            'hours' => $hours,
            'webhook_total' => $webhookTotal,
            'webhook_delivered' => $webhookDelivered,
            'webhook_failed' => $webhookFailed,
            'webhook_success_rate' => $this->rate($webhookDelivered, $webhookTotal),
            'moodle_total' => $moodleTotal,
            'moodle_success' => $moodleSuccess,
            'moodle_failed' => $moodleFailed,
            'moodle_success_rate' => $this->rate($moodleSuccess, $moodleTotal),
            'webhook_retrying' => WebhookLog::query()
                ->where('status', 'retrying')
                ->when($systemId, fn ($q) => $q->where('client_system_id', $systemId))
                ->count(),
            'moodle_retrying' => MoodleSyncLog::query()
                ->where('status', 'retrying')
                ->when($systemId, fn ($q) => $q->where('client_system_id', $systemId))
                ->count(),
        ];

        $recentWebhookFailures = WebhookLog::with('clientSystem:id,name,slug')
            ->whereIn('status', ['failed', 'retrying'])
            ->when($systemId, fn ($q) => $q->where('client_system_id', $systemId))
            ->latest()
            ->limit(10)
            ->get();

        $recentMoodleFailures = MoodleSyncLog::with('clientSystem:id,name,slug')
            ->whereIn('status', ['failed', 'retrying'])
            ->when($systemId, fn ($q) => $q->where('client_system_id', $systemId))
            ->latest()
            ->limit(10)
            ->get();

        $systemHealth = ClientSystem::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('id', $systemId))
            ->withCount([
                'webhookLogs as webhook_total_count' => fn ($q) => $q->where('created_at', '>=', $since),
                'webhookLogs as webhook_failed_count' => fn ($q) => $q->where('created_at', '>=', $since)->whereIn('status', ['failed', 'retrying']),
                'moodleSyncLogs as moodle_total_count' => fn ($q) => $q->where('created_at', '>=', $since),
                'moodleSyncLogs as moodle_failed_count' => fn ($q) => $q->where('created_at', '>=', $since)->whereIn('status', ['failed', 'retrying']),
            ])
            ->orderBy('name')
            ->get();

        return view('monitor.operacoes', compact(
            'stats',
            'recentWebhookFailures',
            'recentMoodleFailures',
            'systemHealth',
            'isSuperAdmin'
        ));
    }

    private function rate(int $success, int $total): float
    {
        if ($total === 0) {
            return 100.0;
        }

        return round(($success / $total) * 100, 1);
    }
}

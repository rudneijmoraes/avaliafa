<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulados\SimuladoInscricaoRequest;
use App\Models\ExamSession;
use App\Models\Simulado;
use App\Models\SimuladoHub;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use App\Services\Api\SessionTokenService;
use App\Services\SimuladoEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SimuladoPublicController extends Controller
{
    public function __construct(
        private readonly SessionTokenService $tokenService,
        private readonly SimuladoEmailService $emailService,
    ) {}

    public function showInscricao(Request $request, string $slug)
    {
        [$baseSimulado, $hub] = $this->resolvePublicEntry($slug);
        $hubSlug = $hub?->slug ?? $this->resolveHubSlug($baseSimulado);
        $simuladosHub = $this->findHubSimulados($baseSimulado, $hubSlug, $hub);
        $selectedSlug = (string) $request->query('simulado', '');
        $selectedSimulado = $simuladosHub->firstWhere('slug', $selectedSlug);

        if (! $selectedSimulado && $selectedSlug !== '') {
            if ($selectedSlug === $baseSimulado->slug) {
                $selectedSimulado = $baseSimulado;
            } else {
                $selectedSimulado = Simulado::query()
                    ->with('exam')
                    ->where('slug', $selectedSlug)
                    ->where('status', 'active')
                    ->first();
            }
        }

        $selectedSimulado = $selectedSimulado ?? $simuladosHub->first() ?? $baseSimulado;

        if (Auth::check()) {
            if ($request->query('from') === 'painel') {
                return $this->redirectLoggedInUserToExam($selectedSimulado);
            }

            return redirect()->route('simulados.minha-area');
        }

        $hasQuestions = $selectedSimulado->exam?->questions()->exists() ?? false;
        $viewerProgress = $this->resolveViewerHubProgress($simuladosHub);
        $captchaFirst = random_int(1, 9);
        $captchaSecond = random_int(1, 9);
        $captchaToken = Str::random(32);
        session()->put('simulado_captcha.'.$captchaToken, $captchaFirst + $captchaSecond);

        return view('simulados.inscricao', [
            'simulado' => $selectedSimulado,
            'hasQuestions' => $hasQuestions,
            'baseSimulado' => $baseSimulado,
            'hub' => $hub,
            'hubSlug' => $hubSlug,
            'simuladosHub' => $simuladosHub,
            'selectedSimulado' => $selectedSimulado,
            'viewerProgress' => $viewerProgress,
            'captchaFirst' => $captchaFirst,
            'captchaSecond' => $captchaSecond,
            'captchaToken' => $captchaToken,
        ]);
    }

    public function storeInscricao(SimuladoInscricaoRequest $request, string $slug)
    {
        [$baseSimulado, $hub] = $this->resolvePublicEntry($slug);
        $hubSlug = $hub?->slug ?? $this->resolveHubSlug($baseSimulado);
        $targetSlug = (string) ($request->validated()['simulado_slug'] ?? '');

        $simulado = $baseSimulado;
        if ($targetSlug !== '' && $targetSlug !== $baseSimulado->slug) {
            $candidate = Simulado::query()
                ->with('exam')
                ->where('slug', $targetSlug)
                ->where('status', 'active')
                ->where('client_system_id', $baseSimulado->client_system_id)
                ->first();

            if ($candidate && $this->resolveHubSlug($candidate) === $hubSlug) {
                $simulado = $candidate;
            }
        }

        if (! $simulado->exam->questions()->exists()) {
            return back()
                ->withInput()
                ->withErrors(['simulado' => 'Este simulado ainda não possui questões vinculadas.']);
        }

        $data = $request->validated();
        $cpf = preg_replace('/\D/', '', (string) $data['cpf']);

        $participant = SimuladoParticipant::withTrashed()
            ->where('client_system_id', $simulado->client_system_id)
            ->where('cpf', $cpf)
            ->first();

        $isNewParticipant = ! $participant;

        if ($participant) {
            $participant->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => preg_replace('/\D/', '', (string) $data['phone']),
                'registered_at' => now(),
            ]);
            if ($participant->trashed()) {
                $participant->restore();
            }
            $participant->save();
        } else {
            $participant = SimuladoParticipant::query()->create([
                'client_system_id' => $simulado->client_system_id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => preg_replace('/\D/', '', (string) $data['phone']),
                'cpf' => $cpf,
                'registered_at' => now(),
            ]);
        }

        $user = $this->firstOrCreateUser($participant, $simulado, $data, $cpf);

        if (! $participant->user_id) {
            $participant->update(['user_id' => $user->id]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        if ($isNewParticipant) {
            $this->emailService->sendWelcomeEmail($participant, (int) $simulado->client_system_id);
        }

        $registration = SimuladoRegistration::query()->firstOrCreate(
            ['simulado_id' => $simulado->id, 'participant_id' => $participant->id],
            ['status' => 'registered', 'registered_at' => now()]
        );

        if ($registration->exam_session_id) {
            $existingSession = ExamSession::query()->find($registration->exam_session_id);
            if ($existingSession
                && in_array($existingSession->status, ['pending', 'in_progress'])
                && ! $existingSession->isExpired()
            ) {
                return redirect()->away($this->tokenService->getDeepLink($existingSession));
            }

            if ($existingSession && $existingSession->isExpired()) {
                $existingSession->update(['status' => 'expired']);
            }
        }

        return redirect()->route('simulados.minha-area');
    }

    private function redirectLoggedInUserToExam(Simulado $simulado): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $simulado->loadMissing('exam');

        $cpf = preg_replace('/\D/', '', (string) ($user->cpf ?? ''));

        $participant = SimuladoParticipant::withTrashed()
            ->where('client_system_id', $simulado->client_system_id)
            ->where(function ($q) use ($user, $cpf) {
                $q->where('user_id', $user->id);
                if ($cpf !== '') {
                    $q->orWhere('cpf', $cpf);
                }
            })
            ->first();

        if (! $participant) {
            $participant = SimuladoParticipant::query()->create([
                'client_system_id' => $simulado->client_system_id,
                'first_name' => $user->first_name ?? $user->name,
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'cpf' => $cpf,
                'user_id' => $user->id,
                'registered_at' => now(),
            ]);
        } else {
            if ($participant->trashed()) {
                $participant->restore();
            }
            if (! $participant->user_id) {
                $participant->update(['user_id' => $user->id]);
            }
        }

        $registration = SimuladoRegistration::query()->firstOrCreate(
            ['simulado_id' => $simulado->id, 'participant_id' => $participant->id],
            ['status' => 'registered', 'registered_at' => now()]
        );

        if ($registration->exam_session_id) {
            $existingSession = ExamSession::query()->find($registration->exam_session_id);
            if ($existingSession
                && in_array($existingSession->status, ['pending', 'in_progress'])
                && ! $existingSession->isExpired()
            ) {
                return redirect()->away($this->tokenService->getDeepLink($existingSession));
            }

            if ($existingSession && $existingSession->isExpired()) {
                $existingSession->update(['status' => 'expired']);
            }
        }

        $completedAttemptsCount = (int) ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $user->id)
            ->where('is_simulation', true)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        $maxAttempts = max(0, (int) data_get($simulado->settings ?? [], 'max_attempts', 0));
        if ($maxAttempts > 0 && $completedAttemptsCount >= $maxAttempts) {
            return redirect()->route('simulados.minha-area')
                ->with('error', "Este simulado permite no máximo {$maxAttempts} tentativa(s).");
        }

        $nextAttemptNumber = (int) ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $user->id)
            ->max('attempt_number') + 1;

        $session = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $user->id,
            'attempt_number' => $nextAttemptNumber,
            'status' => 'pending',
            'expires_at' => now()->addMinutes((int) $simulado->exam->duration_minutes + 30),
            'is_simulation' => true,
            'launch_source' => 'simulado',
        ]);

        $registration->update([
            'exam_session_id' => $session->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return redirect()->away($this->tokenService->getDeepLink($session));
    }

    private function resolvePublicEntry(string $slug): array
    {
        $hub = SimuladoHub::query()
            ->with([
                'simulados' => fn ($query) => $query
                    ->with('exam')
                    ->where('status', 'active')
                    ->orderBy('hub_order')
                    ->orderBy('id'),
            ])
            ->where('slug', $slug)
            ->where('status', '!=', 'archived')
            ->first();

        if ($hub && $hub->simulados->isNotEmpty()) {
            return [$hub->simulados->first(), $hub];
        }

        $baseSimulado = Simulado::query()
            ->with(['exam', 'hub'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return [$baseSimulado, $baseSimulado->hub];
    }

    private function resolveHubSlug(Simulado $simulado): string
    {
        if ($simulado->relationLoaded('hub') && $simulado->hub) {
            return $simulado->hub->slug;
        }

        if ($simulado->hub_id) {
            $hubSlug = SimuladoHub::query()->whereKey($simulado->hub_id)->value('slug');

            if ($hubSlug) {
                return (string) $hubSlug;
            }
        }

        $hub = trim((string) data_get($simulado->settings ?? [], 'public_hub_slug', ''));

        return $hub !== '' ? $hub : $simulado->slug;
    }

    private function findHubSimulados(Simulado $baseSimulado, string $hubSlug, ?SimuladoHub $hub = null)
    {
        if ($hub) {
            return $hub->simulados
                ->where('status', 'active')
                ->values();
        }

        return Simulado::query()
            ->with(['exam', 'hub'])
            ->where('client_system_id', $baseSimulado->client_system_id)
            ->where('status', 'active')
            ->get()
            ->filter(function (Simulado $simulado) use ($hubSlug) {
                return $this->resolveHubSlug($simulado) === $hubSlug;
            })
            ->sortByDesc('id')
            ->values();
    }

    private function firstOrCreateUser(
        SimuladoParticipant $participant,
        Simulado $simulado,
        array $data,
        string $cpf
    ): User {
        $password = Hash::make((string) ($data['password'] ?? $cpf));

        if ($participant->user_id) {
            $existing = User::query()->find($participant->user_id);
            if ($existing) {
                $existing->update([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'password' => $password,
                ]);

                return $existing;
            }
        }

        $user = User::query()
            ->where('client_system_id', $simulado->client_system_id)
            ->where('cpf', $cpf)
            ->first();

        if ($user) {
            $user->update([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'role' => 'student',
                'active' => true,
                'password' => $password,
            ]);

            return $user;
        }

        return User::query()->create([
            'client_system_id' => $simulado->client_system_id,
            'role' => 'student',
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'cpf' => $cpf,
            'password' => $password,
            'active' => true,
        ]);
    }

    private function resolveViewerHubProgress($simuladosHub): ?array
    {
        $user = Auth::user();

        if (! $user || $simuladosHub->isEmpty()) {
            return null;
        }

        $participant = SimuladoParticipant::query()
            ->where('client_system_id', $simuladosHub->first()->client_system_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return null;
        }

        $registrations = SimuladoRegistration::query()
            ->where('participant_id', $participant->id)
            ->whereIn('simulado_id', $simuladosHub->pluck('id'))
            ->get()
            ->keyBy('simulado_id');

        $items = $simuladosHub->map(function (Simulado $simulado) use ($registrations) {
            $registration = $registrations->get($simulado->id);
            $completed = ! is_null($registration?->completed_at);

            return [
                'simulado_id' => $simulado->id,
                'simulado_slug' => $simulado->slug,
                'name' => $simulado->name,
                'completed' => $completed,
                'status' => $registration?->status ?? 'not_started',
                'percentage_correct' => (float) ($registration?->percentage_correct ?? 0),
            ];
        })->values();

        $completedCount = $items->where('completed', true)->count();
        $pendingCount = $items->count() - $completedCount;

        return [
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'progress_percentage' => $items->count() > 0
                ? round(($completedCount / $items->count()) * 100, 1)
                : 0,
            'items' => $items,
        ];
    }
}

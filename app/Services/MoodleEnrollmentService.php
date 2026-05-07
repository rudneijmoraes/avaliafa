<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\Api\SessionTokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoodleEnrollmentService
{
    public function __construct(
        private readonly MoodleActivityResolver $activityResolver,
        private readonly SessionTokenService $tokenService,
    ) {}

    /**
     * Fetch enrolled students from one or more Moodle courses.
     *
     * @param  array<int>  $extraCourseIds  Additional course IDs beyond the exam's configured one
     * @return array<int, array> Moodle user objects filtered to student role (deduplicated by Moodle ID)
     */
    public function fetchEnrolledStudents(Exam $exam, array $extraCourseIds = []): array
    {
        $moodle = $this->activityResolver->resolveForExam($exam);

        if (! $moodle || empty($moodle['url']) || empty($moodle['token'])) {
            throw new \RuntimeException('Configuração Moodle incompleta para esta prova (url ou token ausentes).');
        }

        $courseIds = collect($extraCourseIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0);

        if (($moodle['course_id'] ?? 0) > 0) {
            $courseIds->prepend((int) $moodle['course_id']);
        }

        $courseIds = $courseIds->unique()->values();

        if ($courseIds->isEmpty()) {
            throw new \RuntimeException('Nenhum Course ID configurado. Informe ao menos um ID de curso do Moodle.');
        }

        $allStudents = collect();
        $baseUrl = rtrim($moodle['url'], '/').'/webservice/rest/server.php';

        foreach ($courseIds as $courseId) {
            $response = Http::timeout(30)->asForm()->post($baseUrl, [
                'wstoken' => $moodle['token'],
                'wsfunction' => 'core_enrol_get_enrolled_users',
                'moodlewsrestformat' => 'json',
                'courseid' => $courseId,
                'options[0][name]' => 'userfields',
                'options[0][value]' => 'id,username,firstname,lastname,email,idnumber,roles',
            ]);

            if (! $response->successful()) {
                Log::warning("Moodle enrollment fetch failed for course {$courseId}", [
                    'status' => $response->status(),
                ]);

                continue;
            }

            $payload = $response->json();

            if (isset($payload['exception'])) {
                Log::warning("Moodle API error for course {$courseId}", [
                    'exception' => $payload['exception'],
                    'message' => $payload['message'] ?? '',
                ]);

                continue;
            }

            if (! is_array($payload)) {
                continue;
            }

            $students = collect($payload)->filter(function ($user) {
                $roles = collect($user['roles'] ?? []);

                return $roles->contains(fn ($r) => ($r['roleid'] ?? 0) == 5
                    || strtolower($r['shortname'] ?? '') === 'student');
            });

            $allStudents = $allStudents->concat($students);
        }

        return $allStudents->unique('id')->values()->all();
    }

    /**
     * Match/create local students and generate exam sessions + JWT links in bulk.
     *
     * @return array<int, array> Result items with status, links, and messages
     */
    public function generateBulkLinks(Exam $exam, array $moodleUsers, string $ipAddress, string $userAgent): array
    {
        $version = $exam->latestVersion;
        $results = [];

        $existingByMoodleId = User::where('client_system_id', $exam->client_system_id)
            ->where('role', 'student')
            ->whereNotNull('moodle_user_id')
            ->get()
            ->keyBy(fn ($u) => (string) $u->moodle_user_id);

        $activeSessions = ExamSession::where('exam_id', $exam->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get()
            ->keyBy('student_id');

        foreach ($moodleUsers as $moodleUser) {
            $moodleId = (int) ($moodleUser['id'] ?? 0);
            $item = [
                'moodle_user_id' => $moodleId,
                'moodle_name' => trim(($moodleUser['firstname'] ?? '').' '.($moodleUser['lastname'] ?? '')),
                'moodle_email' => $moodleUser['email'] ?? '',
                'status' => null,
                'student_id' => null,
                'student_name' => null,
                'session_id' => null,
                'link' => null,
                'message' => null,
            ];

            try {
                $student = $existingByMoodleId->get((string) $moodleId);

                if ($student) {
                    $item['student_id'] = $student->id;
                    $item['student_name'] = $student->name;
                } else {
                    $student = $this->autoCreateStudent($exam, $moodleUser);
                    $item['student_id'] = $student->id;
                    $item['student_name'] = $student->name;
                    $existingByMoodleId->put((string) $moodleId, $student);
                }

                if ($activeSessions->has($student->id)) {
                    $existingSession = $activeSessions->get($student->id);
                    $link = $this->tokenService->getDeepLink($existingSession->fresh('exam'));
                    $item['status'] = 'already_active';
                    $item['session_id'] = $existingSession->id;
                    $item['link'] = $link;
                    $item['message'] = "Sessão #{$existingSession->id} já ativa (tentativa {$existingSession->attempt_number}).";
                    $results[] = $item;

                    continue;
                }

                $attemptNumber = $this->nextAttemptNumber($exam->id, $student->id);

                $session = ExamSession::create([
                    'exam_id' => $exam->id,
                    'exam_version_id' => $version?->id,
                    'student_id' => $student->id,
                    'attempt_number' => $attemptNumber,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes($exam->duration_minutes + 10),
                    'is_simulation' => false,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'face_status' => $exam->face_recognition_enabled ? 'pending' : 'not_required',
                ]);

                $link = $this->tokenService->getDeepLink($session->fresh('exam'));

                $item['session_id'] = $session->id;
                $item['link'] = $link;

                if (! $existingByMoodleId->has((string) $moodleId) || $item['student_name'] === $student->name) {
                    $wasCreated = $student->wasRecentlyCreated;
                    $item['status'] = $wasCreated ? 'created' : 'link_generated';
                    $item['message'] = $wasCreated
                        ? 'Estudante criado automaticamente e link gerado.'
                        : 'Link gerado com sucesso.';
                } else {
                    $item['status'] = 'link_generated';
                    $item['message'] = 'Link gerado com sucesso.';
                }
            } catch (\Throwable $e) {
                $item['status'] = 'error';
                $item['message'] = $e->getMessage();
                Log::warning('Bulk link generation error', [
                    'exam_id' => $exam->id,
                    'moodle_user_id' => $moodleId,
                    'error' => $e->getMessage(),
                ]);
            }

            $results[] = $item;
        }

        return $results;
    }

    private function autoCreateStudent(Exam $exam, array $moodleUser): User
    {
        $firstName = $moodleUser['firstname'] ?? 'Moodle';
        $lastName = $moodleUser['lastname'] ?? 'User';
        $email = $moodleUser['email'] ?? null;
        $moodleId = (int) $moodleUser['id'];
        $cpf = preg_replace('/\D/', '', $moodleUser['idnumber'] ?? '');

        if (strlen($cpf) !== 11) {
            $cpf = null;
        }

        if ($cpf && User::where('cpf', $cpf)->exists()) {
            $existing = User::where('cpf', $cpf)
                ->where('client_system_id', $exam->client_system_id)
                ->where('role', 'student')
                ->first();

            if ($existing) {
                $existing->update(['moodle_user_id' => $moodleId]);

                return $existing;
            }

            $cpf = null;
        }

        if (! $cpf) {
            $cpf = 'MDL'.str_pad($moodleId, 8, '0', STR_PAD_LEFT);
        }

        if ($email && User::where('email', $email)->where('client_system_id', $exam->client_system_id)->exists()) {
            $email = null;
        }

        return User::create([
            'client_system_id' => $exam->client_system_id,
            'role' => 'student',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'cpf' => $cpf,
            'email' => $email,
            'moodle_user_id' => $moodleId,
            'password' => Hash::make($cpf),
            'active' => true,
        ]);
    }

    private function nextAttemptNumber(int $examId, int $studentId): int
    {
        $max = ExamSession::where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->max('attempt_number');

        return ($max ?? 0) + 1;
    }
}

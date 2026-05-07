<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\MoodleSyncLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleSyncService
{
    // Backoff intervals in seconds: 1min, 5min, 30min, 2h, 24h, 24h
    private const RETRY_DELAYS = [60, 300, 1800, 7200, 86400, 86400];

    public function __construct(private readonly MoodleActivityResolver $activityResolver) {}

    public function sync(ExamSession $session): bool
    {
        $clientSystem = $session->exam->clientSystem;

        $moodle = $this->activityResolver->resolveForSession($session);

        if (! $moodle) {
            return true;
        }

        $log = MoodleSyncLog::create([
            'session_id' => $session->id,
            'client_system_id' => $clientSystem->id,
            'status' => 'pending',
            'grade_sent' => $session->final_score,
        ]);

        try {
            if (($moodle['course_id'] ?? 0) <= 0 || ($moodle['activity_id'] ?? 0) <= 0) {
                throw new \RuntimeException('Moodle course_id/activity_id não configurados para esta prova.');
            }

            if (empty($session->student?->moodle_user_id)) {
                throw new \RuntimeException('moodle_user_id do estudante não informado.');
            }

            // Resolve component type from cmid (Moodle 4.1 expects cmid, not instance ID)
            $resolved = $this->resolveModuleInfo($moodle);
            $activityId = $resolved['cmid'];
            $component = $resolved['component'];

            $grade = $this->convertGrade($session, $moodle);

            Log::info('Moodle sync attempt', [
                'session_id' => $session->id,
                'moodle_url' => $moodle['url'],
                'course_id' => $moodle['course_id'],
                'cmid_configured' => $moodle['activity_id'],
                'cmid_used' => $activityId,
                'component_configured' => $moodle['component'],
                'component_resolved' => $component,
                'student_moodle_id' => $session->student->moodle_user_id,
                'grade_sent' => $grade,
                'raw_score' => $session->final_score,
                'scale' => $moodle['scale'],
            ]);

            $response = Http::asForm()->post(
                $moodle['url'].'/webservice/rest/server.php',
                [
                    'wstoken' => $moodle['token'],
                    'wsfunction' => 'core_grades_update_grades',
                    'moodlewsrestformat' => 'json',
                    'source' => 'AvaliaFA',
                    'courseid' => $moodle['course_id'] ?? 0,
                    'component' => $component,
                    'activityid' => $activityId,
                    'itemnumber' => $moodle['itemnumber'] ?? 0,
                    'grades[0][studentid]' => $session->student->moodle_user_id,
                    'grades[0][grade]' => $grade,
                    'grades[0][str_feedback]' => 'Nota lançada pelo AvaliaFA',
                ]
            );

            $payload = $response->json();

            Log::info('Moodle sync response', [
                'session_id' => $session->id,
                'http_status' => $response->status(),
                'payload_type' => gettype($payload),
                'payload' => $payload,
                'raw_body' => mb_substr($response->body(), 0, 500),
            ]);

            if ($this->isSuccessfulGradeUpdateResponse($response, $payload)) {
                $log->update([
                    'status' => 'success',
                    'moodle_response' => $this->normalizeResponsePayload($payload, $response),
                    'synced_at' => now(),
                ]);

                $session->update(['moodle_synced' => true]);

                return true;
            }

            throw new \RuntimeException('Moodle returned unexpected response: '.$response->body());
        } catch (\Throwable $e) {
            Log::error('Moodle sync failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            $retryCount = $log->retry_count;
            $nextDelay = self::RETRY_DELAYS[$retryCount] ?? self::RETRY_DELAYS[array_key_last(self::RETRY_DELAYS)];
            $maxRetries = count(self::RETRY_DELAYS);

            $log->update([
                'status' => $retryCount >= $maxRetries ? 'failed' : 'retrying',
                'error_message' => $e->getMessage(),
                'retry_count' => $retryCount + 1,
                'next_retry_at' => $retryCount < $maxRetries ? now()->addSeconds($nextDelay) : null,
            ]);

            return false;
        }
    }

    /**
     * Moodle 4.1 core_grades_update_grades expects the course module ID (cmid),
     * NOT the activity instance ID. The cmid is what appears in URLs like
     * mod/lti/view.php?id=1409.
     *
     * This method uses core_course_get_contents to detect the real component type
     * (mod_assign, mod_lti, mod_quiz, etc.) for the given cmid.
     *
     * @return array{cmid: int, component: string}
     */
    private function resolveModuleInfo(array $moodle): array
    {
        $cmid = (int) ($moodle['activity_id'] ?? 0);
        $courseId = (int) ($moodle['course_id'] ?? 0);
        $defaultComponent = $moodle['component'] ?? 'mod_assign';

        $fallback = ['cmid' => $cmid, 'component' => $defaultComponent];

        if ($cmid <= 0 || $courseId <= 0) {
            return $fallback;
        }

        try {
            $response = Http::asForm()->post(
                $moodle['url'].'/webservice/rest/server.php',
                [
                    'wstoken' => $moodle['token'],
                    'wsfunction' => 'core_course_get_contents',
                    'moodlewsrestformat' => 'json',
                    'courseid' => $courseId,
                ]
            );

            if (! $response->successful()) {
                Log::warning('Moodle module resolve: course contents request failed', [
                    'cmid' => $cmid,
                    'course_id' => $courseId,
                    'status' => $response->status(),
                ]);

                return $fallback;
            }

            $payload = $response->json();

            if (! is_array($payload) || isset($payload['exception'])) {
                Log::warning('Moodle module resolve: course contents error', [
                    'cmid' => $cmid,
                    'error' => $payload['message'] ?? $payload['errorcode'] ?? 'unknown',
                ]);

                return $fallback;
            }

            foreach ($payload as $section) {
                foreach (($section['modules'] ?? []) as $module) {
                    if ((int) ($module['id'] ?? 0) !== $cmid) {
                        continue;
                    }

                    $modname = (string) ($module['modname'] ?? '');
                    $resolvedComponent = $modname !== '' ? "mod_{$modname}" : $defaultComponent;

                    Log::info('Moodle module resolved', [
                        'cmid' => $cmid,
                        'instance_id' => $module['instance'] ?? 'unknown',
                        'module_name' => $module['name'] ?? 'unknown',
                        'modname' => $modname,
                        'component' => $resolvedComponent,
                        'note' => 'Using cmid (not instance_id) for core_grades_update_grades',
                    ]);

                    return ['cmid' => $cmid, 'component' => $resolvedComponent];
                }
            }

            Log::warning('Moodle module resolve: cmid not found in course contents', [
                'cmid' => $cmid,
                'course_id' => $courseId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Moodle module resolve exception', [
                'cmid' => $cmid,
                'error' => $e->getMessage(),
            ]);
        }

        return $fallback;
    }

    private function convertGrade(ExamSession $session, array $moodle): float
    {
        $scale = $moodle['scale'] ?? '0-100';
        $score = (float) $session->final_score;

        $targetMax = match ($scale) {
            '0-10' => 10.0,
            '0-100', 'percent' => 100.0,
            default => 100.0,
        };

        return $session->exam->normalizeScore($score, $targetMax, $session->scoringQuestionIds());
    }

    /**
     * Moodle core_grades_update_grades returns:
     *   GRADE_UPDATE_OK = 0       (success)
     *   GRADE_UPDATE_FAILED = 1   (failure)
     *   GRADE_UPDATE_ITEM_LOCKED = 2
     *   GRADE_UPDATE_MULTIPLE = 3
     *   null                      (success in some Moodle versions)
     */
    private function isSuccessfulGradeUpdateResponse(Response $response, mixed $payload): bool
    {
        if (! $response->successful()) {
            return false;
        }

        // null response means success in some Moodle versions
        if ($payload === null) {
            $body = trim($response->body());

            return $body === '' || $body === 'null' || $body === '0';
        }

        if (is_array($payload)) {
            if (isset($payload['exception']) || isset($payload['errorcode'])) {
                return false;
            }

            return array_key_exists('result', $payload)
                || array_key_exists('status', $payload);
        }

        if (is_bool($payload)) {
            return $payload;
        }

        // GRADE_UPDATE_OK = 0 means success; >= 1 means failure
        if (is_int($payload) || is_float($payload)) {
            return $payload === 0 || $payload === 0.0;
        }

        if (is_string($payload) && is_numeric(trim($payload))) {
            return (int) trim($payload) === 0;
        }

        return false;
    }

    private function normalizeResponsePayload(mixed $payload, Response $response): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        return [
            'status_flag' => $payload,
            'raw_body' => $response->body(),
        ];
    }
}

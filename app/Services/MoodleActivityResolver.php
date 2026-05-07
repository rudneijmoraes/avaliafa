<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleActivityResolver
{
    public function resolveForSession(ExamSession $session): ?array
    {
        $session->loadMissing(['exam.clientSystem']);

        return $this->resolveForExam($session->exam);
    }

    public function resolveForExam(Exam $exam): ?array
    {
        $exam->loadMissing('clientSystem');

        $systemConfig = $exam->clientSystem?->moodle_config ?? [];

        $url = $this->examSetting($exam, ['moodle.url', 'moodle_url']) ?: ($exam->clientSystem?->getMoodleUrl());
        $token = $this->examSetting($exam, ['moodle.token', 'moodle_token']) ?: ($systemConfig['token'] ?? null);
        $courseId = $this->toInt($this->examSetting($exam, ['moodle.course_id', 'moodle_course_id', 'integration.moodle.course_id']))
            ?? $this->toInt($systemConfig['course_id'] ?? null)
            ?? 0;
        $scale = $this->examSetting($exam, ['moodle.scale', 'moodle_scale']) ?: ($systemConfig['scale'] ?? '0-100');
        $component = $this->examSetting($exam, ['moodle.component', 'moodle_component']) ?: 'mod_assign';
        $itemNumber = $this->toInt($this->examSetting($exam, ['moodle.itemnumber', 'moodle_itemnumber'])) ?? 0;

        if (empty($url) || empty($token)) {
            return null;
        }

        // 1st priority: exam-level activity_id (set per-exam in settings)
        $activityId = $this->toInt($this->examSetting($exam, ['moodle.activity_id', 'moodle_activity_id', 'integration.moodle.activity_id'])) ?? 0;

        // 2nd priority: resolve by exam title in Moodle course
        if ($activityId <= 0 && $courseId > 0) {
            $activityName = $this->examSetting($exam, ['moodle.activity_name', 'moodle_activity_name', 'integration.moodle.activity_name'])
                ?: $exam->title;

            $resolvedActivityId = $this->resolveActivityIdByName(
                $exam,
                (string) $url,
                (string) $token,
                $courseId,
                (string) $activityName,
            );

            if ($resolvedActivityId) {
                $activityId = $resolvedActivityId;
            }
        }

        // 3rd priority: system-level activity_id (global fallback)
        if ($activityId <= 0) {
            $activityId = $this->toInt($systemConfig['activity_id'] ?? null) ?? 0;

            if ($activityId > 0) {
                Log::info('MoodleActivityResolver: using system-level activity_id as fallback', [
                    'exam_id' => $exam->id,
                    'exam_title' => $exam->title,
                    'activity_id' => $activityId,
                ]);
            }
        }

        return [
            'url' => rtrim((string) $url, '/'),
            'token' => (string) $token,
            'activity_id' => $activityId,
            'course_id' => $courseId,
            'scale' => (string) $scale,
            'component' => (string) $component,
            'itemnumber' => $itemNumber,
        ];
    }

    private function resolveActivityIdByName(
        Exam $exam,
        string $url,
        string $token,
        int $courseId,
        string $activityName,
    ): ?int {
        try {
            $response = Http::asForm()->post(
                rtrim($url, '/').'/webservice/rest/server.php',
                [
                    'wstoken' => $token,
                    'wsfunction' => 'core_course_get_contents',
                    'moodlewsrestformat' => 'json',
                    'courseid' => $courseId,
                ]
            );

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                return null;
            }

            $needle = mb_strtolower(trim($activityName));
            if ($needle === '') {
                return null;
            }

            foreach ($payload as $section) {
                foreach (($section['modules'] ?? []) as $module) {
                    $moduleName = mb_strtolower((string) ($module['name'] ?? ''));

                    if ($moduleName === '' || ! str_contains($moduleName, $needle)) {
                        continue;
                    }

                    $resolvedId = $this->toInt($module['id'] ?? null);
                    if (! $resolvedId) {
                        continue;
                    }

                    Log::info('MoodleActivityResolver: resolved activity by name', [
                        'exam_id' => $exam->id,
                        'exam_title' => $exam->title,
                        'moodle_activity_name' => $module['name'] ?? '',
                        'cmid' => $resolvedId,
                    ]);

                    // Persist only to exam settings (not to ClientSystem global config)
                    $this->persistResolvedActivityId($exam, $resolvedId);

                    return $resolvedId;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('MoodleActivityResolver: name resolution failed', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Persist the resolved activity_id to the exam's own settings only.
     * Does NOT overwrite the ClientSystem global config.
     */
    private function persistResolvedActivityId(Exam $exam, int $activityId): void
    {
        $settings = $exam->settings ?? [];
        Arr::set($settings, 'moodle.activity_id', $activityId);
        $exam->forceFill(['settings' => $settings])->save();
    }

    private function examSetting(Exam $exam, array $paths): mixed
    {
        $settings = $exam->settings ?? [];

        foreach ($paths as $path) {
            $value = Arr::get($settings, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}

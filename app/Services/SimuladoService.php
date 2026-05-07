<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Simulado;
use App\Models\SimuladoHub;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SimuladoService
{
    public function create(array $data, User $user): Simulado
    {
        $clientSystemId = $user->isSuperAdmin()
            ? (int) ($data['client_system_id'] ?? 0)
            : (int) $user->client_system_id;
        $slug = $this->generateSlug((string) $data['name']);
        $hub = $this->hubFeaturesAvailable() ? $this->resolveHubSafely($data, $clientSystemId, $slug) : null;

        $existingExamId = (int) ($data['existing_exam_id'] ?? 0);
        if ($existingExamId > 0) {
            $exam = Exam::query()->findOrFail($existingExamId);
        } else {
            $exam = Exam::query()->create([
                'client_system_id' => $clientSystemId,
                'created_by' => $user->id,
                'title' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $this->resolveExamStatus((string) $data['status']),
                'duration_minutes' => (int) ($data['duration_minutes'] ?? 60),
                'max_violations' => (int) ($data['max_violations'] ?? 3),
                'webcam_enabled' => (bool) ($data['webcam_enabled'] ?? false),
                'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? true),
                'shuffle_choices' => (bool) ($data['shuffle_choices'] ?? true),
                'passing_score' => (float) ($data['passing_score'] ?? 6),
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'settings' => $this->buildExamSettings($data),
            ]);
        }

        $payload = [
            'exam_id' => $exam->id,
            'client_system_id' => $clientSystemId,
            'created_by' => $user->id,
            'template_id' => $data['template_id'] ?? null,
            'slug' => $slug,
            'name' => (string) $data['name'],
            'description' => $data['description'] ?? null,
            'status' => (string) $data['status'],
            'capture_photo_enabled' => (bool) ($data['capture_photo_enabled'] ?? false),
            'webcam_enabled' => (bool) ($data['webcam_enabled'] ?? false),
            'fullscreen_enabled' => (bool) ($data['fullscreen_enabled'] ?? true),
            'show_result_immediately' => (bool) ($data['show_result_immediately'] ?? true),
            'auto_email_enabled' => (bool) ($data['auto_email_enabled'] ?? true),
            'activation_notified_at' => null,
            'moodle_integration_enabled' => (bool) ($data['moodle_integration_enabled'] ?? false),
            'settings' => $this->buildSimuladoSettings($data),
        ];

        if ($this->hubFeaturesAvailable()) {
            $payload['hub_id'] = $hub?->id;
            $payload['hub_order'] = $this->resolveNextHubOrder($hub);
        }

        return Simulado::query()->create($payload);
    }

    public function update(Simulado $simulado, array $data): Simulado
    {
        $exam = $simulado->exam()->withTrashed()->first();

        if (! $exam) {
            throw new \RuntimeException("Simulado #{$simulado->id} sem prova vinculada. Não é possível salvar.");
        }

        $hubFeaturesAvailable = $this->hubFeaturesAvailable();
        $hub = $hubFeaturesAvailable ? $this->resolveHubSafely($data, (int) $simulado->client_system_id, $simulado->slug) : null;
        $hubOrder = $hubFeaturesAvailable ? (int) $simulado->hub_order : 1;

        if ($hubFeaturesAvailable && ($hub?->id ?? null) !== $simulado->hub_id) {
            $hubOrder = $this->resolveNextHubOrder($hub);
        }

        $exam->update([
            'title' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $this->resolveExamStatus((string) $data['status'], $exam->status),
            'duration_minutes' => (int) $data['duration_minutes'],
            'max_violations' => (int) $data['max_violations'],
            'webcam_enabled' => (bool) ($data['webcam_enabled'] ?? false),
            'shuffle_questions' => (bool) ($data['shuffle_questions'] ?? true),
            'shuffle_choices' => (bool) ($data['shuffle_choices'] ?? true),
            'passing_score' => (float) $data['passing_score'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'settings' => $this->buildExamSettings($data, $exam->settings ?? []),
        ]);

        $payload = [
            'template_id' => $data['template_id'] ?? null,
            'name' => (string) $data['name'],
            'description' => $data['description'] ?? null,
            'status' => (string) $data['status'],
            'capture_photo_enabled' => (bool) ($data['capture_photo_enabled'] ?? false),
            'webcam_enabled' => (bool) ($data['webcam_enabled'] ?? false),
            'fullscreen_enabled' => (bool) ($data['fullscreen_enabled'] ?? true),
            'show_result_immediately' => (bool) ($data['show_result_immediately'] ?? true),
            'auto_email_enabled' => (bool) ($data['auto_email_enabled'] ?? true),
            'activation_notified_at' => ($data['status'] ?? null) === 'scheduled' ? null : $simulado->activation_notified_at,
            'moodle_integration_enabled' => (bool) ($data['moodle_integration_enabled'] ?? false),
            'settings' => $this->buildSimuladoSettings($data, $simulado->settings ?? []),
        ];

        if ($hubFeaturesAvailable) {
            $payload['hub_id'] = $hub?->id;
            $payload['hub_order'] = $hubOrder > 0 ? $hubOrder : 1;
        }

        $simulado->update($payload);

        return $simulado->fresh(['exam', 'template']);
    }

    private function buildExamSettings(array $data, array $base = []): array
    {
        $settings = $base;
        Arr::set($settings, 'simulado.enabled', true);
        Arr::set($settings, 'simulado.capture_photo_enabled', (bool) ($data['capture_photo_enabled'] ?? false));
        Arr::set($settings, 'simulado.fullscreen_enabled', (bool) ($data['fullscreen_enabled'] ?? true));
        Arr::set($settings, 'simulado.show_result_immediately', (bool) ($data['show_result_immediately'] ?? true));
        Arr::set($settings, 'simulado.auto_email_enabled', (bool) ($data['auto_email_enabled'] ?? true));
        Arr::set($settings, 'simulado.moodle_integration_enabled', (bool) ($data['moodle_integration_enabled'] ?? false));
        Arr::set($settings, 'simulado.max_attempts', (int) ($data['max_attempts'] ?? 0));
        Arr::set($settings, 'simulado.intro_text', trim((string) ($data['intro_text'] ?? '')));
        Arr::set($settings, 'simulado.layout_mode', (string) ($data['layout_mode'] ?? 'step'));
        Arr::set($settings, 'branding_watermark_text', trim((string) ($data['branding_watermark_text'] ?? '')));

        Arr::set($settings, 'moodle.course_id', (int) ($data['moodle_course_id'] ?? 0));
        Arr::set($settings, 'moodle.activity_id', (int) ($data['moodle_activity_id'] ?? 0));
        Arr::set($settings, 'moodle.activity_name', (string) ($data['moodle_activity_name'] ?? ''));

        return $settings;
    }

    private function resolveExamStatus(string $simuladoStatus, ?string $fallback = 'draft'): string
    {
        return match ($simuladoStatus) {
            'active' => 'active',
            'draft', 'scheduled', 'inactive', 'archived' => 'draft',
            default => $fallback ?? 'draft',
        };
    }

    private function buildSimuladoSettings(array $data, array $base = []): array
    {
        $settings = $base;
        Arr::set($settings, 'moodle.course_id', (int) ($data['moodle_course_id'] ?? 0));
        Arr::set($settings, 'moodle.activity_id', (int) ($data['moodle_activity_id'] ?? 0));
        Arr::set($settings, 'moodle.activity_name', (string) ($data['moodle_activity_name'] ?? ''));
        Arr::set($settings, 'public_hub_slug', Str::slug((string) ($data['public_hub_slug'] ?? '')));
        Arr::set($settings, 'weekly_label', trim((string) ($data['weekly_label'] ?? '')));
        Arr::set($settings, 'max_attempts', (int) ($data['max_attempts'] ?? 0));
        Arr::set($settings, 'branding_watermark_text', trim((string) ($data['branding_watermark_text'] ?? '')));
        Arr::set($settings, 'intro_text', trim((string) ($data['intro_text'] ?? '')));
        Arr::set($settings, 'layout_mode', (string) ($data['layout_mode'] ?? 'step'));
        Arr::set($settings, 'notify_participants_on_save', (bool) ($data['notify_participants_on_save'] ?? false));

        return $settings;
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'simulado';
        $counter = 1;

        while (Simulado::withTrashed()->where('slug', $slug)->exists()) {
            $counter++;
            $slug = $base.'-'.$counter;
        }

        return $slug;
    }

    private function resolveHub(array $data, int $clientSystemId, string $fallbackSlug): ?SimuladoHub
    {
        $selectedHubId = (int) ($data['hub_id'] ?? 0);

        if ($selectedHubId > 0) {
            $selectedHub = SimuladoHub::query()
                ->whereKey($selectedHubId)
                ->where('client_system_id', $clientSystemId)
                ->first();

            if ($selectedHub) {
                return $selectedHub;
            }
        }

        $hubSlug = Str::slug((string) ($data['public_hub_slug'] ?? ''));
        $hubSlug = $hubSlug !== '' ? $hubSlug : $fallbackSlug;

        if ($hubSlug === '') {
            return null;
        }

        $hubName = trim((string) ($data['weekly_label'] ?? '')) ?: (string) ($data['name'] ?? 'Hub de Simulados');

        return SimuladoHub::query()->firstOrCreate(
            [
                'client_system_id' => $clientSystemId,
                'slug' => $hubSlug,
            ],
            [
                'name' => $hubName,
                'landing_title' => $hubName,
                'description' => $data['description'] ?? null,
                'status' => in_array(($data['status'] ?? 'active'), ['draft', 'active', 'inactive', 'archived'], true)
                    ? (string) $data['status']
                    : 'active',
            ]
        );
    }

    private function resolveHubSafely(array $data, int $clientSystemId, string $fallbackSlug): ?SimuladoHub
    {
        try {
            return $this->resolveHub($data, $clientSystemId, $fallbackSlug);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function resolveNextHubOrder(?SimuladoHub $hub): int
    {
        if (! $hub) {
            return 1;
        }

        return ((int) $hub->simulados()->max('hub_order')) + 1;
    }

    private function hubFeaturesAvailable(): bool
    {
        try {
            return Schema::hasTable('simulado_hubs')
                && Schema::hasColumn('simulados', 'hub_id')
                && Schema::hasColumn('simulados', 'hub_order');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

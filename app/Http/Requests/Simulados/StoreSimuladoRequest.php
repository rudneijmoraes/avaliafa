<?php

namespace App\Http\Requests\Simulados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class StoreSimuladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isProfessor();
    }

    public function rules(): array
    {
        $rules = [
            'client_system_id' => 'nullable|exists:client_systems,id',
            'name' => 'required|string|max:180',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,scheduled,active,inactive,archived',
            'existing_exam_id' => 'nullable|exists:exams,id',
            'duration_minutes' => 'required_without:existing_exam_id|nullable|integer|min:5|max:300',
            'passing_score' => 'required_without:existing_exam_id|nullable|numeric|min:0|max:100',
            'max_violations' => 'required_without:existing_exam_id|nullable|integer|min:1|max:20',
            'max_attempts' => 'required|integer|min:0|max:99',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_choices' => 'nullable|boolean',
            'notify_participants_on_save' => 'nullable|boolean',
            'capture_photo_enabled' => 'nullable|boolean',
            'webcam_enabled' => 'nullable|boolean',
            'fullscreen_enabled' => 'nullable|boolean',
            'show_result_immediately' => 'nullable|boolean',
            'auto_email_enabled' => 'nullable|boolean',
            'moodle_integration_enabled' => 'nullable|boolean',
            'moodle_course_id' => 'nullable|integer|min:0',
            'moodle_activity_id' => 'nullable|integer|min:0',
            'moodle_activity_name' => 'nullable|string|max:180',
            'public_hub_slug' => 'nullable|string|max:180',
            'weekly_label' => 'nullable|string|max:120',
            'branding_watermark_text' => 'nullable|string|max:80',
            'intro_text' => 'nullable|string|max:25000',
            'layout_mode' => 'nullable|in:step,single_page',
            'template_id' => 'nullable|exists:simulado_email_templates,id',
        ];

        if ($this->hubFeaturesAvailable()) {
            $rules['hub_id'] = 'nullable|exists:simulado_hubs,id';
        } else {
            $rules['hub_id'] = 'nullable|integer|min:1';
        }

        return $rules;
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

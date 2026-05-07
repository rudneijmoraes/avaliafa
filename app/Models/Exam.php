<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exams';

    protected $fillable = [
        'client_system_id',
        'discipline_id',
        'created_by',
        'title',
        'description',
        'status',
        'duration_minutes',
        'max_violations',
        'webcam_enabled',
        'face_recognition_enabled',
        'shuffle_questions',
        'shuffle_choices',
        'passing_score',
        'settings',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'max_violations' => 'integer',
        'passing_score' => 'decimal:2',
        'settings' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'webcam_enabled' => 'boolean',
        'face_recognition_enabled' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_choices' => 'boolean',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('exam_block_id', 'order', 'weight')
            ->orderByPivot('order');
    }

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function blocks()
    {
        return $this->hasMany(ExamBlock::class)->orderBy('order');
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    public function simulado()
    {
        return $this->hasOne(Simulado::class);
    }

    public function versions()
    {
        return $this->hasMany(ExamVersion::class)->orderByDesc('version_number');
    }

    public function ltiResourceLinks()
    {
        return $this->hasMany(LtiResourceLink::class);
    }

    public function latestVersion()
    {
        return $this->hasOne(ExamVersion::class)->latestOfMany('version_number');
    }

    public function requiresWebcam(): bool
    {
        return $this->webcam_enabled || $this->face_recognition_enabled;
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['published', 'active']);
    }

    public function hasMoodleConfig(): bool
    {
        $this->loadMissing('clientSystem');

        $settings = $this->settings ?? [];
        $systemConfig = $this->clientSystem?->moodle_config ?? [];

        $url = data_get($settings, 'moodle.url') ?: ($this->clientSystem?->getMoodleUrl());
        $token = data_get($settings, 'moodle.token') ?: ($systemConfig['token'] ?? null);
        $courseId = data_get($settings, 'moodle.course_id') ?: ($systemConfig['course_id'] ?? null);

        return ! empty($url) && ! empty($token);
    }

    public function hasLtiRegistration(): bool
    {
        $this->loadMissing('clientSystem');

        return $this->clientSystem?->hasLtiConfiguration() ?? false;
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->isAfter($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function totalPossibleScore(?array $questionIds = null): float
    {
        $query = $this->examQuestions();

        if ($questionIds !== null) {
            $ids = collect($questionIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            if ($ids === []) {
                return 0.0;
            }

            $query->whereIn('question_id', $ids);
        }

        return round((float) ($query->sum('weight') ?? 0), 2);
    }

    public function normalizeScore(float $score, float $targetMaximum, ?array $questionIds = null): float
    {
        if ($targetMaximum <= 0) {
            return 0.0;
        }

        $totalPossible = $this->totalPossibleScore($questionIds);

        if ($totalPossible <= 0) {
            return round(max(0.0, min($targetMaximum, $score)), 4);
        }

        return round(max(0.0, min($targetMaximum, ($score / $totalPossible) * $targetMaximum)), 4);
    }
}

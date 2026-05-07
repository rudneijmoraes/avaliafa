<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exam_sessions';

    protected $fillable = [
        'exam_id',
        'exam_version_id',
        'student_id',
        'token_jti',
        'launch_source',
        'lti_registration_id',
        'lti_resource_link_id',
        'attempt_number',
        'status',
        'started_at',
        'submitted_at',
        'expires_at',
        'raw_score',
        'final_score',
        'passed',
        'violation_count',
        'question_order',
        'choice_order',
        'grade_published',
        'moodle_synced',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'device_metadata',
        'risk_score',
        'is_simulation',
        'face_status',
        'face_checks_failed',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'raw_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'violation_count' => 'integer',
        'risk_score' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'question_order' => 'array',
        'choice_order' => 'array',
        'device_metadata' => 'array',
        'passed' => 'boolean',
        'grade_published' => 'boolean',
        'moodle_synced' => 'boolean',
        'is_simulation' => 'boolean',
        'face_checks_failed' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examVersion()
    {
        return $this->belongsTo(ExamVersion::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'session_id');
    }

    public function securityEvents()
    {
        return $this->hasMany(SecurityEvent::class, 'session_id');
    }

    public function snapshots()
    {
        return $this->hasMany(Snapshot::class, 'session_id');
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'session_id');
    }

    public function ltiRegistration()
    {
        return $this->belongsTo(LtiRegistration::class);
    }

    public function ltiResourceLink()
    {
        return $this->belongsTo(LtiResourceLink::class);
    }

    public function moodleSyncLogs()
    {
        return $this->hasMany(MoodleSyncLog::class, 'session_id');
    }

    public function simuladoRegistration()
    {
        return $this->hasOne(SimuladoRegistration::class, 'exam_session_id');
    }

    public function faceVerifications()
    {
        return $this->hasMany(FaceVerification::class);
    }

    public function latestFaceVerification()
    {
        return $this->hasOne(FaceVerification::class)->latestOfMany();
    }

    public function isFaceVerified(): bool
    {
        return $this->face_status === 'verified';
    }

    public function requiresFaceVerification(): bool
    {
        return $this->face_status !== 'not_required';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function hasReachedViolationLimit(): bool
    {
        return $this->violation_count >= $this->exam?->max_violations;
    }

    public function getRemainingSeconds(): int
    {
        if (! $this->expires_at) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    public function wasLaunchedFromLti(): bool
    {
        return $this->launch_source === 'lti';
    }

    public function timeSpentInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->submitted_at) {
            return null;
        }

        return max(0, $this->started_at->diffInSeconds($this->submitted_at));
    }

    public function formattedTimeSpent(): string
    {
        $seconds = $this->timeSpentInSeconds();

        if ($seconds === null) {
            return '-';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02dh %02dmin', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%02dmin %02ds', $minutes, $remainingSeconds);
        }

        return sprintf('%02ds', $remainingSeconds);
    }

    public function scoringQuestionIds(): array
    {
        $ids = collect($this->question_order ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        $this->loadMissing('exam');

        if (! $this->exam) {
            return [];
        }

        return $this->exam->questions()
            ->pluck('questions.id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    public function totalPossibleScore(): float
    {
        $this->loadMissing('exam');

        if (! $this->exam) {
            return 0.0;
        }

        return $this->exam->totalPossibleScore($this->scoringQuestionIds());
    }
}

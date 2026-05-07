<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceVerification extends Model
{
    protected $fillable = [
        'exam_session_id',
        'student_id',
        'type',
        'result',
        'confidence',
        'snapshot_path',
        'metadata',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'confidence' => 'float',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function isApproved(): bool
    {
        return $this->result === 'approved';
    }

    public function isFailed(): bool
    {
        return in_array($this->result, ['failed', 'no_face', 'multiple_faces']);
    }

    public function confidencePercentage(): int
    {
        return (int) round(($this->confidence ?? 0) * 100);
    }

    public function scopeApproved($query)
    {
        return $query->where('result', 'approved');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('result', ['failed', 'no_face', 'multiple_faces']);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('exam_session_id', $sessionId);
    }
}

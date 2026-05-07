<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'version_number',
        'snapshot',
        'questions_count',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'published_at' => 'datetime',
    ];

    // Relationships

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}

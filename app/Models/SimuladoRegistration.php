<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimuladoRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulado_id',
        'participant_id',
        'exam_session_id',
        'status',
        'registered_at',
        'started_at',
        'completed_at',
        'email_sent_at',
        'raw_score',
        'final_score',
        'total_correct',
        'total_wrong',
        'percentage_correct',
        'metadata',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'raw_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'percentage_correct' => 'decimal:2',
        'total_correct' => 'integer',
        'total_wrong' => 'integer',
        'metadata' => 'array',
    ];

    public function simulado()
    {
        return $this->belongsTo(Simulado::class);
    }

    public function participant()
    {
        return $this->belongsTo(SimuladoParticipant::class, 'participant_id');
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoodleSyncLog extends Model
{
    use HasFactory;

    protected $table = 'moodle_sync_logs';

    protected $fillable = [
        'session_id',
        'client_system_id',
        'status',
        'grade_sent',
        'moodle_response',
        'error_message',
        'retry_count',
        'next_retry_at',
        'synced_at',
    ];

    protected $casts = [
        'grade_sent' => 'decimal:2',
        'moodle_response' => 'array',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $table = 'webhook_logs';

    protected $fillable = [
        'client_system_id',
        'event',
        'payload',
        'status_code',
        'status',
        'retry_count',
        'delivered_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'retry_count' => 'integer',
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }
}

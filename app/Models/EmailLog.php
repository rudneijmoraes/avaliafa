<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'client_system_id',
        'simulado_id',
        'type',
        'recipient_email',
        'recipient_name',
        'subject',
        'status',
        'error_message',
        'sent_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at'   => 'datetime',
        'failed_at' => 'datetime',
        'metadata'  => 'array',
    ];

    public function clientSystem(): BelongsTo
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function simulado(): BelongsTo
    {
        return $this->belongsTo(Simulado::class);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForSystem(Builder $query, int $systemId): Builder
    {
        return $query->where('client_system_id', $systemId);
    }

    public function scopeInPeriod(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public static function record(
        string $type,
        ?Simulado $simulado,
        string $recipientEmail,
        ?string $recipientName,
        ?string $subject,
        ?string $error = null,
        array $metadata = []
    ): self {
        $status = $error === null ? 'sent' : 'failed';

        return static::create([
            'client_system_id' => $simulado?->client_system_id,
            'simulado_id'      => $simulado?->id,
            'type'             => $type,
            'recipient_email'  => $recipientEmail,
            'recipient_name'   => $recipientName,
            'subject'          => $subject,
            'status'           => $status,
            'error_message'    => $error,
            'sent_at'          => $status === 'sent'   ? now() : null,
            'failed_at'        => $status === 'failed' ? now() : null,
            'metadata'         => $metadata ?: null,
        ]);
    }
}

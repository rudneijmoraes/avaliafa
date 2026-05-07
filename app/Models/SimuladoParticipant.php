<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimuladoParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_system_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'cpf',
        'metadata',
        'registered_at',
        'last_access_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'registered_at' => 'datetime',
        'last_access_at' => 'datetime',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registrations()
    {
        return $this->hasMany(SimuladoRegistration::class, 'participant_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }
}

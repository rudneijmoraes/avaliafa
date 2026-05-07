<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LtiRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_system_id',
        'issuer',
        'client_id',
        'deployment_id',
        'platform_name',
        'auth_login_url',
        'auth_token_url',
        'keyset_url',
        'settings',
        'last_validated_at',
        'last_error',
        'active',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_validated_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function resourceLinks()
    {
        return $this->hasMany(LtiResourceLink::class);
    }

    public function launchLogs()
    {
        return $this->hasMany(LtiLaunchLog::class);
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    public function isReady(): bool
    {
        return $this->active
            && ! empty($this->issuer)
            && ! empty($this->client_id);
    }
}

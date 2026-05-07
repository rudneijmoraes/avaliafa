<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientSystem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'client_id',
        'client_secret',
        'webhook_url',
        'moodle_config',
        'settings',
        'allowed_ips',
        'active',
    ];

    protected $casts = [
        'moodle_config' => 'array',
        'settings' => 'array',
        'allowed_ips' => 'array',
        'active' => 'boolean',
    ];

    protected $hidden = [
        'client_secret',
        'moodle_config',
    ];

    // Relationships

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function moodleSyncLogs()
    {
        return $this->hasMany(MoodleSyncLog::class);
    }

    public function ltiRegistrations()
    {
        return $this->hasMany(LtiRegistration::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('name');
    }

    // Helpers

    public function hasMoodleIntegration(): bool
    {
        return ! empty($this->getMoodleUrl()) && ! empty($this->moodle_config['token']);
    }

    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    public function getMoodleUrl(): ?string
    {
        if ($this->slug === 'certificadora' && ! empty($this->moodle_config['certifier_url'])) {
            return $this->moodle_config['certifier_url'];
        }

        return $this->moodle_config['url'] ?? null;
    }

    public function getMoodleToken(): ?string
    {
        return $this->moodle_config['token'] ?? null;
    }

    public function getLtiConfig(): array
    {
        $config = $this->moodle_config['lti'] ?? [];

        return is_array($config) ? $config : [];
    }

    public function hasLtiConfiguration(): bool
    {
        $config = $this->getLtiConfig();

        return ! empty($config['issuer']) && ! empty($config['client_id']);
    }

    public function getLtiIssuer(): ?string
    {
        return $this->getLtiConfig()['issuer'] ?? null;
    }

    public function getLtiClientId(): ?string
    {
        return $this->getLtiConfig()['client_id'] ?? null;
    }

    public function getLtiDeploymentId(): ?string
    {
        return $this->getLtiConfig()['deployment_id'] ?? null;
    }
}

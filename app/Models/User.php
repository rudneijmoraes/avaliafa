<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'client_system_id',
        'role',
        'name',
        'first_name',
        'last_name',
        'email',
        'cpf',
        'phone',
        'password',
        'external_id',
        'moodle_user_id',
        'active',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    // Relationships

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function examSessions()
    {
        return $this->hasMany(ExamSession::class, 'student_id');
    }

    public function createdExams()
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'student_id');
    }

    public function simuladoParticipant()
    {
        return $this->hasOne(SimuladoParticipant::class);
    }

    // Role helpers

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isCoordinator(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'coordinator']);
    }

    public function isProfessor(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'professor']);
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isCommercial(): bool
    {
        return $this->role === 'coordinator';
    }

    public function canManageExams(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'professor'], true);
    }

    public function canAccessReports(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'coordinator', 'professor'], true);
    }

    public function canAccessSimuladoCrm(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'coordinator', 'professor'], true);
    }

    public function canManagePeople(): bool
    {
        return in_array($this->role, ['super_admin', 'admin'], true);
    }

    public function canAccessMonitoring(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'professor'], true);
    }

    // Profile photo

    public function hasProfilePhoto(): bool
    {
        $path = $this->normalizedProfilePhotoPath();

        return $path !== null && Storage::disk('public')->exists($path);
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->hasProfilePhoto()) {
            return null;
        }

        return route('media.profile-photo', $this);
    }

    public function normalizedProfilePhotoPath(): ?string
    {
        $path = trim((string) $this->profile_photo_path);

        if ($path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);

        foreach (['/storage/', 'storage/', '/public/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        $marker = 'profile-photos/';
        $markerPosition = strpos($path, $marker);

        if ($markerPosition !== false) {
            $path = substr($path, $markerPosition);
        }

        $path = ltrim($path, '/');

        return $path !== '' ? $path : null;
    }
}

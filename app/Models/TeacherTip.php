<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherTip extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'simulado_id',
        'title',
        'description',
        'video_url',
        'video_type',
        'active',
        'order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order'  => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function simulado(): BelongsTo
    {
        return $this->belongsTo(Simulado::class);
    }

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(ClientSystem::class, 'teacher_tip_systems');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeForSystem(Builder $query, int $systemId): Builder
    {
        return $query->whereHas('systems', fn ($q) => $q->where('client_system_id', $systemId));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderByDesc('created_at');
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $url = $this->video_url;

        if ($this->video_type === 'youtube') {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/', $url, $m);
            return isset($m[1]) ? "https://www.youtube.com/embed/{$m[1]}" : null;
        }

        if ($this->video_type === 'vimeo') {
            preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m);
            return isset($m[1]) ? "https://player.vimeo.com/video/{$m[1]}" : null;
        }

        return null;
    }

    public static function detectVideoType(string $url): string
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        return 'vimeo';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    use HasFactory;

    protected $table = 'snapshots';

    protected $fillable = [
        'session_id',
        'path',
        'sha256_hash',
        'trigger',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function normalizedPath(): ?string
    {
        $path = $this->path;

        if (! is_string($path) || $path === '') {
            return null;
        }

        foreach (['public/', '/public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return ltrim(substr($path, strlen($prefix)), '/');
            }
        }

        return ltrim($path, '/');
    }

    public function fileUrl(): string
    {
        return route('media.snapshot', $this);
    }

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }
}

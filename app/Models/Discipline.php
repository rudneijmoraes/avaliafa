<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Discipline extends Model
{
    use HasFactory;

    /** Returns empty collection if the disciplines table has not been migrated yet. */
    public static function safeAll(?int $clientSystemId = null, bool $activeOnly = true): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('disciplines')) {
            return collect();
        }

        return static::query()
            ->when($activeOnly, fn ($q) => $q->where('active', true))
            ->when($clientSystemId !== null, fn ($q) => $q->where('client_system_id', $clientSystemId))
            ->orderBy('name')
            ->get();
    }

    protected $fillable = [
        'client_system_id',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public static function findOrCreateByName(string $name, int $clientSystemId): self
    {
        $name = trim($name);

        return static::firstOrCreate(
            ['client_system_id' => $clientSystemId, 'name' => $name],
            ['active' => true],
        );
    }
}

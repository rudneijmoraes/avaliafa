<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimuladoHub extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_system_id',
        'name',
        'slug',
        'description',
        'landing_title',
        'status',
        'starts_at',
        'ends_at',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function simulados()
    {
        return $this->hasMany(Simulado::class, 'hub_id')->orderBy('hub_order')->orderBy('id');
    }
}

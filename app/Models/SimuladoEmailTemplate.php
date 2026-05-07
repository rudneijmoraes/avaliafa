<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimuladoEmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_system_id',
        'created_by',
        'name',
        'subject',
        'html_body',
        'active',
        'settings',
    ];

    protected $casts = [
        'active' => 'boolean',
        'settings' => 'array',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chamado extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_system_id',
        'created_by',
        'protocol',
        'subject',
        'message',
        'status',
        'priority',
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

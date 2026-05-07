<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Simulado extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'client_system_id',
        'hub_id',
        'hub_order',
        'created_by',
        'template_id',
        'slug',
        'name',
        'description',
        'status',
        'capture_photo_enabled',
        'webcam_enabled',
        'fullscreen_enabled',
        'show_result_immediately',
        'auto_email_enabled',
        'activation_notified_at',
        'broadcast_sent_at',
        'broadcast_total_sent',
        'broadcast_total_target',
        'moodle_integration_enabled',
        'settings',
    ];

    protected $casts = [
        'capture_photo_enabled' => 'boolean',
        'webcam_enabled' => 'boolean',
        'fullscreen_enabled' => 'boolean',
        'show_result_immediately' => 'boolean',
        'auto_email_enabled' => 'boolean',
        'activation_notified_at'  => 'datetime',
        'broadcast_sent_at'       => 'datetime',
        'broadcast_total_sent'    => 'integer',
        'broadcast_total_target'  => 'integer',
        'moodle_integration_enabled' => 'boolean',
        'hub_order' => 'integer',
        'settings' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hub()
    {
        return $this->belongsTo(SimuladoHub::class, 'hub_id');
    }

    public function template()
    {
        return $this->belongsTo(SimuladoEmailTemplate::class, 'template_id');
    }

    public function registrations()
    {
        return $this->hasMany(SimuladoRegistration::class);
    }

    public function participants()
    {
        return $this->belongsToMany(
            SimuladoParticipant::class,
            'simulado_registrations',
            'simulado_id',
            'participant_id'
        )->withPivot([
            'id',
            'status',
            'registered_at',
            'started_at',
            'completed_at',
            'email_sent_at',
            'final_score',
        ]);
    }
}

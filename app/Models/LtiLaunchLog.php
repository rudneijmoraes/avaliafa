<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LtiLaunchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lti_registration_id',
        'lti_resource_link_id',
        'exam_session_id',
        'message_type',
        'user_sub',
        'user_email',
        'roles',
        'claims',
        'is_successful',
        'error_message',
        'launched_at',
    ];

    protected $casts = [
        'roles' => 'array',
        'claims' => 'array',
        'is_successful' => 'boolean',
        'launched_at' => 'datetime',
    ];

    public function registration()
    {
        return $this->belongsTo(LtiRegistration::class, 'lti_registration_id');
    }

    public function resourceLink()
    {
        return $this->belongsTo(LtiResourceLink::class, 'lti_resource_link_id');
    }

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}

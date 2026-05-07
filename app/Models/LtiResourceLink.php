<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LtiResourceLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lti_registration_id',
        'exam_id',
        'resource_link_id',
        'context_id',
        'context_label',
        'context_title',
        'lineitem_url',
        'lineitems_url',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function registration()
    {
        return $this->belongsTo(LtiRegistration::class, 'lti_registration_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function launchLogs()
    {
        return $this->hasMany(LtiLaunchLog::class);
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}

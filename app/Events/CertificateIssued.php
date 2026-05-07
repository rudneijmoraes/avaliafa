<?php

namespace App\Events;

use App\Models\Certificate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CertificateIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Certificate $certificate) {}
}

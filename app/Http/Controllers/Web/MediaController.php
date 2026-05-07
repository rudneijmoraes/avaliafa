<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function profilePhoto(User $user): BinaryFileResponse
    {
        $path = $user->normalizedProfilePhotoPath();

        abort_if($path === null || ! Storage::disk('public')->exists($path), 404);

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]
        );
    }

    public function snapshot(Request $request, Snapshot $snapshot): BinaryFileResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $snapshot->loadMissing('session.exam');

        abort_if($user === null, 403);
        abort_if(
            ! $user->isSuperAdmin()
            && (
                $user->role === 'student'
                || $user->client_system_id !== $snapshot->session?->exam?->client_system_id
            ),
            403
        );

        $path = $snapshot->normalizedPath();

        abort_if($path === null || ! Storage::disk('public')->exists($path), 404);

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }
}

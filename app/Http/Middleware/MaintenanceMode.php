<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    private static string $flagFile = '';

    public static function flagPath(): string
    {
        if (self::$flagFile === '') {
            self::$flagFile = storage_path('app/.maintenance');
        }

        return self::$flagFile;
    }

    public static function isActive(): bool
    {
        return file_exists(self::flagPath());
    }

    public static function enable(): void
    {
        file_put_contents(self::flagPath(), now()->toIso8601String());
    }

    public static function disable(): void
    {
        if (file_exists(self::flagPath())) {
            unlink(self::flagPath());
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::isActive()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && ($user->isProfessor() || $user->isSuperAdmin())) {
            return $next($request);
        }

        if ($request->is('login', 'logout', 'admin/manutencao/toggle')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sistema em manutenção.'], 503);
        }

        return response(view('maintenance'), 503);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentSessionTimeout
{
    const TIMEOUT_MINUTES = 10;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'student') {
            $lastActivity = $request->session()->get('student_last_activity');

            if ($lastActivity && (now()->timestamp - (int) $lastActivity) > self::TIMEOUT_MINUTES * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Sua sessão expirou por inatividade. Faça login novamente.');
            }

            $request->session()->put('student_last_activity', now()->timestamp);
        }

        return $next($request);
    }
}

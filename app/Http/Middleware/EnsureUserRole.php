<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($roles === []) {
            return $next($request);
        }

        $allowed = collect($roles)
            ->flatMap(fn ($role) => explode(',', (string) $role))
            ->map(fn ($role) => trim((string) $role))
            ->filter()
            ->map(function (string $role) {
                return match ($role) {
                    'comercial' => 'coordinator',
                    'criador_prova' => 'professor',
                    'administrador' => 'admin',
                    default => $role,
                };
            })
            ->unique()
            ->values()
            ->all();

        if ($user->role === 'super_admin' || in_array($user->role, $allowed, true)) {
            return $next($request);
        }

        abort(403, 'Acesso não autorizado para o perfil atual.');
    }
}


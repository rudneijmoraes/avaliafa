<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthWebController extends Controller
{
    public function showLogin(Request $request): View
    {
        $simuladoSlug = trim((string) $request->query('simulado', ''));
        $redirect = trim((string) $request->query('redirect', ''));

        if ($simuladoSlug !== '') {
            $request->session()->put('url.intended', route('simulados.minha-area'));
        } elseif ($redirect !== '' && str_starts_with($redirect, '/')) {
            // Accept only internal relative paths to avoid open redirect.
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login', [
            'simuladoSlug' => $simuladoSlug,
        ]);
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'cpf' => 'required|string',
            'password' => 'required|string',
        ]);

        // Aceita CPF com ou sem máscara
        $cpf = preg_replace('/\D/', '', $request->input('cpf_digits') ?: $request->input('cpf'));

        $user = User::where('cpf', $cpf)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return back()
                ->withInput(['cpf' => $request->input('cpf')])
                ->withErrors(['cpf' => 'CPF ou senha incorretos.']);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();


        Log::info('Login successful', [
            'user_id' => $user->id,
            'role' => $user->role,
            'session_driver' => config('session.driver'),
            'session_id' => $request->session()->getId(),
            'is_secure' => $request->isSecure(),
            'url_scheme' => $request->getScheme(),
        ]);

        $defaultRoute = $user->isStudent()
            ? route('simulados.minha-area')
            : route('dashboard');

        return redirect()->intended($defaultRoute);
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

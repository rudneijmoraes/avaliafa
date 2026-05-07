<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    private function fullName(string $firstName, string $lastName): string
    {
        return trim($firstName.' '.$lastName);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $fallbackName = $this->splitName($user->name ?? '');

        return view('perfil.index', [
            'user' => $user,
            'firstName' => $user->first_name ?: $fallbackName['first_name'],
            'lastName' => $user->last_name ?: $fallbackName['last_name'],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => $this->fullName($data['first_name'], $data['last_name']),
            'email' => $data['email'] ?: null,
        ]);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function atualizarFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = Auth::user();
        $oldPath = $user->profile_photo_path;

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $storedPath = Storage::disk('public')->putFileAs(
            "profile-photos/{$user->id}",
            $request->file('foto'),
            'photo.jpg'
        );

        $user->update(['profile_photo_path' => $storedPath]);

        return back()->with('success', 'Foto atualizada com sucesso.');
    }

    public function showSenha()
    {
        return view('perfil.senha');
    }

    public function updateSenha(Request $request)
    {
        $request->validate([
            'senha_atual' => 'required|string',
            'senha_nova' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->senha_atual, $user->password)) {
            return back()->withErrors(['senha_atual' => 'Senha atual incorreta.']);
        }

        $user->update(['password' => Hash::make($request->senha_nova)]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }
}

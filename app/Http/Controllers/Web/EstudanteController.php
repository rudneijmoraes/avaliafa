<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstudanteController extends Controller
{
    private function currentUser(): User
    {
        /** @var User */
        return Auth::user();
    }

    private function fullName(string $firstName, string $lastName): string
    {
        return trim($firstName.' '.$lastName);
    }

    private function mapProfileToRole(string $profile): string
    {
        return match ($profile) {
            'administrador' => 'admin',
            'criador_prova' => 'professor',
            'comercial' => 'coordinator',
            default => 'student',
        };
    }

    private function profileLabelFromRole(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrador',
            'professor' => 'Criador de Prova',
            'coordinator' => 'Comercial',
            default => 'Estudante',
        };
    }

    public function index(Request $request)
    {
        $query = User::where('role', 'student')
            ->with('clientSystem')
            ->withCount('examSessions');

        if (! $this->currentUser()->isSuperAdmin()) {
            $query->where('client_system_id', $this->currentUser()->client_system_id);
        }

        if ($request->filled('busca')) {
            $search = $request->busca;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('cpf', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($request->filled('sistema') && $this->currentUser()->isSuperAdmin()) {
            $query->where('client_system_id', $request->sistema);
        }

        if ($request->filled('ativo')) {
            $query->where('active', $request->ativo === '1');
        }

        $students = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        $systems = $this->currentUser()->isSuperAdmin() ? ClientSystem::active()->get() : collect();

        $isSuperAdmin = $this->currentUser()->isSuperAdmin();
        $mySystemId   = $this->currentUser()->client_system_id;

        $totalStudents = User::where('role', 'student')
            ->when(! $isSuperAdmin, fn ($q) => $q->where('client_system_id', $mySystemId))
            ->count();

        $systemStats = User::where('role', 'student')
            ->when(! $isSuperAdmin, fn ($q) => $q->where('users.client_system_id', $mySystemId))
            ->join('client_systems', 'users.client_system_id', '=', 'client_systems.id')
            ->selectRaw('client_systems.name as system_name, count(*) as total')
            ->groupBy('client_systems.id', 'client_systems.name')
            ->orderBy('client_systems.name')
            ->pluck('total', 'system_name');

        return view('estudantes.index', compact('students', 'systems', 'totalStudents', 'systemStats'));
    }

    public function create()
    {
        $systems = ClientSystem::active()->get();

        return view('estudantes.form', ['systems' => $systems, 'student' => null]);
    }

    public function store(Request $request)
    {
        $request->merge(['cpf' => preg_replace('/\D/', '', (string) $request->input('cpf', ''))]);

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'cpf' => 'required|string|size:11|unique:users,cpf',
            'email' => 'nullable|email|max:255|unique:users,email',
            'client_system_id' => 'required|exists:client_systems,id',
            'active' => 'nullable|boolean',
        ]);

        User::create([
            'client_system_id' => $request->client_system_id,
            'role' => 'student',
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $this->fullName($request->first_name, $request->last_name),
            'cpf' => $request->cpf,
            'phone' => $request->phone ?: null,
            'email' => $request->email ?: null,
            'active' => $request->boolean('active', true),
            'password' => Hash::make($request->cpf),
        ]);

        return redirect()->route('estudantes.index')->with('success', 'Estudante cadastrado com sucesso. Senha inicial: CPF sem formatação.');
    }

    public function show(User $estudante)
    {
        $estudante->load('clientSystem');
        $estudante->loadCount('examSessions');

        $sessions = $estudante->examSessions()
            ->with('exam')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('estudantes.show', compact('estudante', 'sessions'));
    }

    public function edit(User $estudante)
    {
        $systems = ClientSystem::active()->get();

        return view('estudantes.form', ['systems' => $systems, 'student' => $estudante]);
    }

    public function update(Request $request, User $estudante)
    {
        $hasMoodleCpf = $estudante->cpf && preg_match('/^MDL/i', $estudante->cpf);
        $currentUser = $this->currentUser();
        $originalRole = $estudante->role;

        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:users,email,'.$estudante->id,
            'client_system_id' => 'required|exists:client_systems,id',
            'active' => 'nullable|boolean',
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['profile'] = 'nullable|in:student,administrador,criador_prova,comercial';
        }

        if ($hasMoodleCpf) {
            $rules['cpf'] = 'nullable|string|size:11|unique:users,cpf,'.$estudante->id;
        }

        $request->validate($rules);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $this->fullName($request->first_name, $request->last_name),
            'email' => $request->email ?: null,
            'phone' => $request->phone ?: null,
            'client_system_id' => $request->client_system_id,
            'active' => $request->boolean('active', true),
        ];

        if ($currentUser->isSuperAdmin() && $request->filled('profile')) {
            $data['role'] = $this->mapProfileToRole((string) $request->input('profile'));
        }

        if ($hasMoodleCpf && $request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', $request->cpf);
            if (strlen($cpf) === 11) {
                $data['cpf'] = $cpf;
            }
        }

        $estudante->update($data);

        if ($currentUser->isSuperAdmin() && $originalRole === 'student' && $estudante->role !== 'student') {
            return redirect()
                ->route('configuracoes.acessos')
                ->with('success', 'Usuário promovido com sucesso para '.$this->profileLabelFromRole($estudante->role).'.');
        }

        return redirect()->route('estudantes.show', $estudante)->with('success', 'Estudante atualizado com sucesso.');
    }

    public function atualizarFoto(Request $request, User $estudante): RedirectResponse
    {
        $request->validate([
            'foto' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $oldPath = $estudante->profile_photo_path;
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $storedPath = Storage::disk('public')->putFileAs(
            "profile-photos/{$estudante->id}",
            $request->file('foto'),
            'photo.jpg'
        );

        $estudante->update(['profile_photo_path' => $storedPath]);

        return back()->with('success', 'Foto atualizada com sucesso.');
    }

    public function resetSenha(User $estudante): RedirectResponse
    {
        $cpf = preg_replace('/\D/', '', (string) $estudante->cpf);

        if (strlen($cpf) !== 11) {
            return back()->withErrors(['senha' => 'Não é possível resetar: o aluno não tem um CPF válido cadastrado.']);
        }

        $estudante->update(['password' => Hash::make($cpf)]);

        return back()->with('success', 'Senha redefinida com sucesso. Nova senha: CPF sem formatação.');
    }

    public function destroy(User $estudante)
    {
        $estudante->delete();

        return redirect()->route('estudantes.index')->with('success', 'Estudante removido.');
    }

    public function importForm(): View
    {
        $systems = $this->currentUser()->isSuperAdmin()
            ? ClientSystem::active()->get()
            : ClientSystem::active()->where('id', $this->currentUser()->client_system_id)->get();

        return view('estudantes.import', compact('systems'));
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($handle, ['primeiro_nome', 'sobrenome', 'cpf', 'email', 'external_id', 'moodle_user_id']);
            fputcsv($handle, ['João',  'da Silva', '12345678901', 'joao@universidade.edu.br',  'EXT-001', '']);
            fputcsv($handle, ['Maria', 'Souza',    '98765432100', 'maria@universidade.edu.br', '',        '42']);
            fclose($handle);
        }, 'modelo-importacao-estudantes.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,txt|max:5120',
            'client_system_id' => 'required|exists:client_systems,id',
            'acao_duplicata' => 'required|in:pular,atualizar',
        ]);

        $systemId = $this->currentUser()->isSuperAdmin()
            ? (int) $request->client_system_id
            : (int) $this->currentUser()->client_system_id;

        $path = $request->file('arquivo')->getRealPath();
        $handle = fopen($path, 'r');

        // Remove BOM UTF-8 se presente
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, ',');
        if (! $header) {
            fclose($handle);

            return back()->withErrors(['arquivo' => 'Arquivo CSV vazio ou inválido.']);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        // Aceita formato novo (primeiro_nome + sobrenome) ou legado (nome)
        $hasNewFormat = in_array('primeiro_nome', $header) && in_array('sobrenome', $header);
        $hasLegacyFormat = in_array('nome', $header);

        if (! $hasNewFormat && ! $hasLegacyFormat) {
            fclose($handle);

            return back()->withErrors(['arquivo' => "Coluna obrigatória ausente: use 'primeiro_nome'+'sobrenome' ou 'nome'. Baixe o modelo para ver o formato correto."]);
        }
        if (! in_array('cpf', $header)) {
            fclose($handle);

            return back()->withErrors(['arquivo' => "Coluna obrigatória ausente: 'cpf'. Baixe o modelo para ver o formato correto."]);
        }

        // Pré-carrega CPFs e emails existentes para evitar N+1 queries no loop
        $cpfCache = User::where('client_system_id', $systemId)
            ->select(['id', 'cpf'])
            ->get()
            ->keyBy('cpf');

        $emailCache = User::where('client_system_id', $systemId)
            ->whereNotNull('email')
            ->pluck('email')
            ->flip()
            ->all();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $row = 1;

        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            $row++;

            if (! array_filter($line)) {
                continue;
            }

            $cols = array_pad(array_slice($line, 0, count($header)), count($header), '');
            $data = array_map('trim', array_combine($header, $cols));

            // Resolve nome: formato novo (primeiro_nome+sobrenome) ou legado (nome)
            if ($hasNewFormat) {
                $firstName = $data['primeiro_nome'] ?? '';
                $lastName = $data['sobrenome'] ?? '';
                $fullName = $this->fullName($firstName, $lastName);
            } else {
                $fullName = $data['nome'] ?? '';
                $parts = explode(' ', $fullName, 2);
                $firstName = $parts[0];
                $lastName = $parts[1] ?? '';
            }

            $cpf = preg_replace('/\D/', '', $data['cpf'] ?? '');

            if (! $fullName) {
                $errors[] = "Linha {$row}: nome em branco.";

                continue;
            }

            if (strlen($cpf) !== 11) {
                $errors[] = "Linha {$row}: CPF '{$data['cpf']}' inválido (deve ter 11 dígitos).";

                continue;
            }

            if ($cpfCache->has($cpf)) {
                if ($request->acao_duplicata === 'atualizar') {
                    $patch = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'name' => $fullName,
                    ];
                    if (! empty($data['email'])) {
                        $patch['email'] = $data['email'];
                    }
                    if (! empty($data['external_id'])) {
                        $patch['external_id'] = $data['external_id'];
                    }
                    if (! empty($data['moodle_user_id'])) {
                        $patch['moodle_user_id'] = $data['moodle_user_id'];
                    }
                    User::where('id', $cpfCache->get($cpf)->id)->update($patch);
                    $updated++;
                } else {
                    $skipped++;
                }

                continue;
            }

            $email = ! empty($data['email']) ? $data['email'] : null;

            if ($email && isset($emailCache[$email])) {
                $errors[] = "Linha {$row}: email '{$email}' já cadastrado neste sistema.";

                continue;
            }

            User::create([
                'client_system_id' => $systemId,
                'role' => 'student',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName,
                'cpf' => $cpf,
                'email' => $email,
                'external_id' => ! empty($data['external_id']) ? $data['external_id'] : null,
                'moodle_user_id' => ! empty($data['moodle_user_id']) ? $data['moodle_user_id'] : null,
                'password' => Hash::make($cpf),
                'active' => true,
            ]);

            // Atualiza caches para evitar duplicata dentro do mesmo arquivo
            $cpfCache->put($cpf, (object) ['id' => 0, 'cpf' => $cpf]);
            if ($email) {
                $emailCache[$email] = true;
            }

            $created++;
        }

        fclose($handle);

        $msg = "Importação concluída: {$created} criados, {$updated} atualizados, {$skipped} pulados.";

        if (! empty($errors)) {
            session(['import_errors' => array_slice($errors, 0, 50)]);
            $msg .= ' '.count($errors).' linha(s) com erro — verifique os detalhes.';

            return redirect()->route('estudantes.import.form')->with('success', $msg);
        }

        return redirect()->route('estudantes.index')->with('success', $msg);
    }

    public function updateFaceReference(Request $request, User $estudante): RedirectResponse
    {
        $request->validate([
            'face_photo' => 'required|image|mimes:jpg,jpeg,png|max:3072',
        ]);

        if ($estudante->face_reference_photo) {
            Storage::disk('private')->delete($estudante->face_reference_photo);
        }

        $path = $request->file('face_photo')->storeAs(
            "face-references/{$estudante->id}",
            'reference.jpg',
            'private'
        );

        $estudante->update(['face_reference_photo' => $path]);

        return back()->with('success', 'Foto de referência cadastrada com sucesso.');
    }

    public function destroyFaceReference(User $estudante): RedirectResponse
    {
        if ($estudante->face_reference_photo) {
            Storage::disk('private')->delete($estudante->face_reference_photo);
            $estudante->update(['face_reference_photo' => null]);
        }

        return back()->with('success', 'Foto de referência removida.');
    }
}

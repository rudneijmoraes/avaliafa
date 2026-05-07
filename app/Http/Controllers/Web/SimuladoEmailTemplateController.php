<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulados\StoreSimuladoEmailTemplateRequest;
use App\Http\Requests\Simulados\UpdateSimuladoEmailTemplateRequest;
use App\Models\ClientSystem;
use App\Models\SimuladoEmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SimuladoEmailTemplateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        abort_unless($user->isProfessor(), 403);

        $templates = SimuladoEmailTemplate::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))
            ->orderByDesc('id')
            ->paginate(20);

        return view('simulados.templates.index', compact('templates'));
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless($user->isProfessor(), 403);

        $systems = $user->isSuperAdmin()
            ? ClientSystem::query()->where('active', true)->orderBy('name')->get()
            : ClientSystem::query()->whereKey($user->client_system_id)->get();

        return view('simulados.templates.form', [
            'template' => null,
            'systems' => $systems,
            'action' => route('simulados.templates.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StoreSimuladoEmailTemplateRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }
        $data['created_by'] = $user->id;
        $data['active'] = (bool) ($data['active'] ?? true);

        SimuladoEmailTemplate::query()->create($data);

        return redirect()->route('simulados.templates.index')->with('success', 'Template criado com sucesso.');
    }

    public function edit(SimuladoEmailTemplate $template)
    {
        $this->authorizeTemplate($template);
        $user = Auth::user();
        $systems = $user->isSuperAdmin()
            ? ClientSystem::query()->where('active', true)->orderBy('name')->get()
            : ClientSystem::query()->whereKey($user->client_system_id)->get();

        return view('simulados.templates.form', [
            'template' => $template,
            'systems' => $systems,
            'action' => route('simulados.templates.update', $template),
            'method' => 'PUT',
        ]);
    }

    public function update(UpdateSimuladoEmailTemplateRequest $request, SimuladoEmailTemplate $template)
    {
        $this->authorizeTemplate($template);
        $data = $request->validated();
        $data['active'] = (bool) ($data['active'] ?? false);
        if (! Auth::user()->isSuperAdmin()) {
            $data['client_system_id'] = Auth::user()->client_system_id;
        }

        $template->update($data);

        return redirect()->route('simulados.templates.index')->with('success', 'Template atualizado com sucesso.');
    }

    public function testEmail(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfessor(), 403);

        $data = $request->validate([
            'email' => 'required|email|max:190',
            'subject' => 'required|string|max:180',
            'html_body' => 'required|string',
        ]);

        $subject = $this->applyVariables($data['subject']);
        $htmlBody = $this->applyVariables($data['html_body']);

        Mail::html($htmlBody, function ($message) use ($data, $subject) {
            $message->to($data['email'])->subject($subject);
        });

        return response()->json([
            'success' => true,
            'message' => 'E-mail de teste enviado com sucesso.',
        ]);
    }

    private function authorizeTemplate(SimuladoEmailTemplate $template): void
    {
        $user = Auth::user();
        abort_unless($user->isProfessor(), 403);
        if (! $user->isSuperAdmin() && $template->client_system_id !== $user->client_system_id) {
            abort(403);
        }
    }

    private function applyVariables(string $content): string
    {
        $map = [
            '{{primeiro_nome}}' => 'Aluno',
            '{{nome_completo}}' => 'Aluno de Teste',
            '{{email}}' => 'aluno.teste@faculdadeanasps.com.br',
            '{{telefone}}' => '(61) 99999-9999',
            '{{cpf}}' => '00000000000',
            '{{nota}}' => '8,50',
            '{{percentual_acertos}}' => '85,00',
            '{{total_acertos}}' => '17',
            '{{total_erros}}' => '3',
            '{{nome_simulado}}' => 'Simulado de Teste',
            '{{data_realizacao}}' => now()->format('d/m/Y H:i'),
        ];

        return str_replace(array_keys($map), array_values($map), $content);
    }
}

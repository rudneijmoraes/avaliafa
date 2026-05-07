<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Discipline;
use App\Models\Question;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestaoController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $hasDisciplines = \Illuminate\Support\Facades\Schema::hasTable('disciplines');
        $eagerLoad = $hasDisciplines
            ? ['clientSystem', 'creator', 'discipline']
            : ['clientSystem', 'creator'];
        $eagerLoad[] = 'correctChoices';
        $query = Question::with($eagerLoad)
            ->withCount('choices');

        if (! $user->isSuperAdmin()) {
            $query->where('client_system_id', $user->client_system_id);
            $query->visibleForUser($user);
        }

        if ($request->filled('busca')) {
            $query->where('content', 'like', '%'.$request->busca.'%');
        }

        if ($request->filled('tipo')) {
            $query->where('type', $request->tipo);
        }

        if ($request->filled('dificuldade')) {
            $query->where('difficulty', $request->dificuldade);
        }

        if ($request->filled('sistema') && $user->isSuperAdmin()) {
            $query->where('client_system_id', $request->sistema);
        }

        if ($request->filled('disciplina')) {
            $query->where('discipline_id', $request->disciplina);
        }

        $questions = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $systems = $user->isSuperAdmin() ? ClientSystem::where('active', true)->orderBy('name')->get() : collect();
        $disciplines = Discipline::safeAll();

        return view('questoes.index', compact('questions', 'systems', 'disciplines'));
    }

    public function create()
    {
        $systems = ClientSystem::where('active', true)->orderBy('name')->get();
        $disciplines = Discipline::safeAll();
        $question = null;

        return view('questoes.form', compact('systems', 'disciplines', 'question'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'client_system_id' => 'required|exists:client_systems,id',
            'discipline_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== '__new__' && !\App\Models\Discipline::where('id', $value)->exists()) {
                    $fail('A disciplina selecionada é inválida.');
                }
            }],
            'new_discipline_name' => 'nullable|string|max:255',
            'type' => 'required|in:multiple_choice,true_false,multiple_answer',
            'difficulty' => 'required|in:easy,medium,hard',
            'content' => 'required|string',
            'explanation' => 'nullable|string',
            'active' => 'nullable|boolean',
            'owner_department' => 'nullable|string|max:100',
            'visibility_scope' => 'nullable|in:private,department,system,global',
            'tags' => 'nullable|string',
            'choices' => 'required|array|min:2',
            'choices.*.text' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
        ]);

        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }

        // Normalize sentinel values before resolving
        if (in_array($data['discipline_id'] ?? null, ['__new__', 'new', ''], true)) {
            $data['discipline_id'] = null;
        }
        $data['discipline_id'] = $this->resolveDisciplineId($data);
        unset($data['new_discipline_name']);
        $data['created_by'] = Auth::id();
        $data['active'] = $request->boolean('active', true);
        $data['visibility_scope'] = $data['visibility_scope'] ?? 'department';
        $data['tags'] = $request->filled('tags') ? array_map('trim', explode(',', $request->tags)) : [];

        $question = Question::create($data);

        foreach ($request->choices as $i => $choice) {
            $question->choices()->create([
                'content' => $choice['text'],
                'is_correct' => (bool) ($choice['is_correct'] ?? false),
                'order' => $i + 1,
            ]);
        }

        $this->auditLogService->log(
            action: 'question.created',
            auditable: $question,
            newValues: [
                'question_id' => $question->id,
                'client_system_id' => $question->client_system_id,
                'type' => $question->type,
                'difficulty' => $question->difficulty,
            ],
            userId: Auth::id(),
            clientSystemId: $question->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->route('questoes.index')->with('success', 'Questão criada com sucesso!');
    }

    public function show(Question $questao)
    {
        $this->authorizeQuestionAccess($questao);
        $questao->load(['clientSystem', 'creator', 'choices']);

        return view('questoes.show', ['questao' => $questao]);
    }

    public function edit(Question $questao)
    {
        $this->authorizeQuestionAccess($questao);
        $questao->load('choices');
        $systems = ClientSystem::where('active', true)->orderBy('name')->get();
        $disciplines = Discipline::safeAll();

        return view('questoes.form', ['systems' => $systems, 'disciplines' => $disciplines, 'question' => $questao]);
    }

    public function update(Request $request, Question $questao)
    {
        $this->authorizeQuestionAccess($questao);

        if ($this->isLockedByPublishedExam($questao)) {
            return redirect()
                ->route('questoes.show', $questao)
                ->with('error', 'Esta questão está vinculada a prova publicada/ativa e só pode ser alterada com nova versão da prova.');
        }

        $oldValues = [
            'content' => $questao->content,
            'difficulty' => $questao->difficulty,
            'type' => $questao->type,
            'active' => (bool) $questao->active,
        ];

        $data = $request->validate([
            'client_system_id' => 'required|exists:client_systems,id',
            'discipline_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== '__new__' && !\App\Models\Discipline::where('id', $value)->exists()) {
                    $fail('A disciplina selecionada é inválida.');
                }
            }],
            'new_discipline_name' => 'nullable|string|max:255',
            'type' => 'required|in:multiple_choice,true_false,multiple_answer',
            'difficulty' => 'required|in:easy,medium,hard',
            'content' => 'required|string',
            'explanation' => 'nullable|string',
            'active' => 'nullable|boolean',
            'owner_department' => 'nullable|string|max:100',
            'visibility_scope' => 'nullable|in:private,department,system,global',
            'tags' => 'nullable|string',
            'choices' => 'required|array|min:2',
            'choices.*.text' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
        ]);

        /** @var User $user */
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }

        // Normalize sentinel values before resolving
        if (in_array($data['discipline_id'] ?? null, ['__new__', 'new', ''], true)) {
            $data['discipline_id'] = null;
        }
        $data['discipline_id'] = $this->resolveDisciplineId($data);
        unset($data['new_discipline_name']);
        $data['active'] = $request->boolean('active', true);
        $data['visibility_scope'] = $data['visibility_scope'] ?? $questao->visibility_scope ?? 'department';
        $data['tags'] = $request->filled('tags') ? array_map('trim', explode(',', $request->tags)) : [];

        $questao->update($data);

        $questao->choices()->delete();
        foreach ($request->choices as $i => $choice) {
            $questao->choices()->create([
                'content' => $choice['text'],
                'is_correct' => (bool) ($choice['is_correct'] ?? false),
                'order' => $i + 1,
            ]);
        }

        $this->auditLogService->log(
            action: 'exam.question.edited',
            auditable: $questao,
            oldValues: $oldValues,
            newValues: [
                'content' => $questao->content,
                'difficulty' => $questao->difficulty,
                'type' => $questao->type,
                'active' => (bool) $questao->active,
            ],
            userId: Auth::id(),
            clientSystemId: $questao->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->route('questoes.show', $questao)->with('success', 'Questão atualizada!');
    }

    public function destroy(Question $questao)
    {
        $this->authorizeQuestionAccess($questao);

        if ($this->isLockedByPublishedExam($questao)) {
            return redirect()
                ->route('questoes.show', $questao)
                ->with('error', 'Esta questão está vinculada a prova publicada/ativa e não pode ser removida sem nova versão da prova.');
        }

        $oldValues = [
            'question_id' => $questao->id,
            'client_system_id' => $questao->client_system_id,
            'type' => $questao->type,
            'difficulty' => $questao->difficulty,
        ];

        $questao->delete();

        $this->auditLogService->log(
            action: 'question.deleted',
            auditable: $questao,
            oldValues: $oldValues,
            userId: Auth::id(),
            clientSystemId: $questao->client_system_id,
        );

        return redirect()->route('questoes.index')->with('success', 'Questão removida.');
    }

    public function importForm()
    {
        /** @var User $user */
        $user = Auth::user();
        $systems = $user->isSuperAdmin()
            ? ClientSystem::where('active', true)->orderBy('name')->get()
            : ClientSystem::where('active', true)->where('id', $user->client_system_id)->get();

        $disciplines = Discipline::safeAll();

        return view('questoes.import', compact('systems', 'disciplines'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|max:51200',
            'client_system_id' => 'required|exists:client_systems,id',
            'acao_duplicata' => 'required|in:pular,importar',
            'discipline_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && $value !== 'new' && !\App\Models\Discipline::where('id', $value)->exists()) {
                    $fail('A disciplina selecionada é inválida.');
                }
            }],
            'discipline_name' => 'nullable|string|max:200',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $systemId = $user->isSuperAdmin()
            ? (int) $request->client_system_id
            : (int) $user->client_system_id;

        $disciplineId = $this->resolveDisciplineId($request, $systemId);

        $file = $request->file('arquivo');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip') {
            $result = $this->parseQtiZip($file->getRealPath(), $systemId, $user->id, $request->acao_duplicata, $disciplineId);
        } elseif ($extension === 'xml') {
            $result = $this->parseXmlAuto($file->getRealPath(), $systemId, $user->id, $request->acao_duplicata, $disciplineId);
        } else {
            $result = $this->parseCsv($file->getRealPath(), $systemId, $user->id, $request->acao_duplicata, $disciplineId);
        }

        if (isset($result['error'])) {
            return back()->withErrors(['arquivo' => $result['error']])->withInput();
        }

        $msg = "{$result['created']} questão(ões) importada(s) com sucesso.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} ignorada(s) (duplicada).";
        }
        if (! empty($result['errors'])) {
            $msg .= ' '.count($result['errors']).' linha(s) com erro.';
        }
        if (! empty($result['discipline'])) {
            $msg .= " Disciplina: {$result['discipline']}.";
        }

        $this->auditLogService->log(
            action: 'question.bulk_imported',
            newValues: [
                'format' => $extension,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors_count' => count($result['errors']),
                'discipline' => $result['discipline'] ?? null,
            ],
            userId: Auth::id(),
            clientSystemId: $systemId,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->route('questoes.import.form')
            ->with('success', $msg)
            ->with('import_errors', $result['errors']);
    }

    public function downloadTemplateCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($handle, ['enunciado', 'tipo', 'dificuldade', 'feedback', 'tags', 'alternativa_1', 'correta_1', 'alternativa_2', 'correta_2', 'alternativa_3', 'correta_3', 'alternativa_4', 'correta_4', 'alternativa_5', 'correta_5']);
            fputcsv($handle, [
                'Qual é a capital do Brasil?', 'multiple_choice', 'easy', 'Brasília é a capital desde 1960.', 'geografia,brasil',
                'São Paulo', '0', 'Brasília', '1', 'Rio de Janeiro', '0', 'Salvador', '0', '', '',
            ]);
            fputcsv($handle, [
                'O sol gira em torno da Terra.', 'true_false', 'easy', 'A Terra gira em torno do Sol.', 'ciencias,astronomia',
                'Verdadeiro', '0', 'Falso', '1', '', '', '', '', '', '',
            ]);
            fclose($handle);
        }, 'modelo-importacao-questoes.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadTemplateCsvBulk(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['enunciado', 'tipo', 'dificuldade', 'feedback', 'tags', 'alternativa_1', 'correta_1', 'alternativa_2', 'correta_2', 'alternativa_3', 'correta_3', 'alternativa_4', 'correta_4', 'alternativa_5', 'correta_5']);
            fputcsv($handle, [
                'Leia o texto base e assinale a alternativa correta sobre a ideia principal.', 'multiple_choice', 'medium', 'A alternativa correta resume a ideia central do texto.', 'cnu,interpretacao,texto-base',
                'Foca apenas em um detalhe secundário.', '0', 'Resume o argumento central do autor.', '1', 'Contradiz o texto apresentado.', '0', 'Não tem relação com o enunciado.', '0', '', '',
            ]);
            fputcsv($handle, [
                'Com base no texto base, marque as alternativas corretas.', 'multiple_answer', 'medium', 'Mais de uma alternativa pode estar correta.', 'cnu,interpretacao,multiplas',
                'Alternativa A correta.', '1', 'Alternativa B incorreta.', '0', 'Alternativa C correta.', '1', 'Alternativa D incorreta.', '0', '', '',
            ]);
            for ($i = 0; $i < 25; $i++) {
                fputcsv($handle, ['', 'multiple_choice', 'medium', '', '', '', '', '', '', '', '', '', '', '', '']);
            }
            fclose($handle);
        }, 'modelo-importacao-questoes-lote.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadImportGuide(): StreamedResponse
    {
        $guide = <<<'TXT'
GUIA RÁPIDO — IMPORTAÇÃO DE QUESTÕES (CSV)

1) Baixe o modelo CSV.
2) Preencha 1 linha por questão.
3) Campos principais:
   - enunciado: texto da questão
   - tipo: multiple_choice | true_false | multiple_answer
   - dificuldade: easy | medium | hard
   - feedback: feedback geral da questão
   - tags: separadas por vírgula
   - alternativa_1...alternativa_5
   - correta_1...correta_5 (1 = correta, 0 = incorreta)

REGRAS MÍNIMAS
- Cada questão precisa de pelo menos 2 alternativas.
- Pelo menos 1 alternativa deve estar marcada como correta.
- O enunciado não pode ficar vazio.

EXEMPLO
enunciado,tipo,dificuldade,feedback,tags,alternativa_1,correta_1,alternativa_2,correta_2
"Qual é a capital do Brasil?",multiple_choice,easy,"Brasília é a capital do país.","geografia,brasil","São Paulo",0,"Brasília",1

DICA
- Salve o arquivo em UTF-8.
- Se usar Excel, revise vírgulas e aspas antes de importar.
TXT;

        return response()->streamDownload(function () use ($guide) {
            echo $guide;
        }, 'guia-importacao-questoes.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function downloadImportGuideAdvanced(): StreamedResponse
    {
        $guide = <<<'MD'
# Guia Avançado — Importação de Questões por CSV

## 1. Estrutura obrigatória
- `enunciado`
- `alternativa_1`, `correta_1`
- `alternativa_2`, `correta_2`

## 2. Estrutura recomendada completa
- `enunciado`
- `tipo`
- `dificuldade`
- `feedback`
- `tags`
- `alternativa_1` ... `alternativa_5`
- `correta_1` ... `correta_5`

## 3. Valores aceitos
### tipo
- `multiple_choice`
- `true_false`
- `multiple_answer`

### dificuldade
- `easy`
- `medium`
- `hard`

### correta_X
- `1` para correta
- `0` para incorreta

## 4. Regras de validação na importação
- Enunciado não pode ficar vazio.
- Cada questão precisa de ao menos 2 alternativas preenchidas.
- Deve existir pelo menos 1 alternativa marcada como correta.
- Linhas totalmente vazias são ignoradas.

## 5. Exemplo completo (múltipla escolha)
```csv
enunciado,tipo,dificuldade,feedback,tags,alternativa_1,correta_1,alternativa_2,correta_2,alternativa_3,correta_3,alternativa_4,correta_4
"Qual é a capital do Brasil?",multiple_choice,easy,"Brasília é a capital oficial desde 1960.","geografia,brasil","São Paulo",0,"Brasília",1,"Rio de Janeiro",0,"Salvador",0
```

## 6. Exemplo completo (múltiplas respostas)
```csv
enunciado,tipo,dificuldade,feedback,tags,alternativa_1,correta_1,alternativa_2,correta_2,alternativa_3,correta_3,alternativa_4,correta_4
"Selecione os mamíferos:",multiple_answer,medium,"Baleia e morcego são mamíferos.","biologia,animais","Baleia",1,"Tubarão",0,"Morcego",1,"Sardinha",0
```

## 7. Erros mais comuns
- Coluna com nome diferente do padrão (`enunciado`, `alternativa_1`, etc.).
- Uso de `sim/nao` em vez de `1/0` nas colunas `correta_X`.
- Arquivo com codificação inválida.
- Vírgulas não escapadas em texto sem aspas.

## 8. Boas práticas
- Use UTF-8.
- Coloque textos com vírgula entre aspas duplas.
- Faça importação piloto com 3 a 5 questões antes de subir lote completo.
- Padronize tags para facilitar filtros depois.

## 9. Compatibilidade
- O campo preferencial é `feedback`.
- O sistema também aceita `explicacao` para arquivos antigos.
MD;

        return response()->streamDownload(function () use ($guide) {
            echo $guide;
        }, 'guia-avancado-importacao-questoes.md', [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function downloadTemplateXml(): StreamedResponse
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<questoes>
    <questao tipo="multiple_choice" dificuldade="easy">
        <enunciado>Qual é a capital do Brasil?</enunciado>
        <explicacao>Brasília é a capital desde 1960.</explicacao>
        <tags>geografia,brasil</tags>
        <alternativas>
            <alternativa correta="0">São Paulo</alternativa>
            <alternativa correta="1">Brasília</alternativa>
            <alternativa correta="0">Rio de Janeiro</alternativa>
            <alternativa correta="0">Salvador</alternativa>
        </alternativas>
    </questao>
    <questao tipo="true_false" dificuldade="easy">
        <enunciado>O sol gira em torno da Terra.</enunciado>
        <explicacao>A Terra gira em torno do Sol.</explicacao>
        <tags>ciencias,astronomia</tags>
        <alternativas>
            <alternativa correta="0">Verdadeiro</alternativa>
            <alternativa correta="1">Falso</alternativa>
        </alternativas>
    </questao>
</questoes>
XML;

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, 'modelo-importacao-questoes.xml', [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function resolveDisciplineId(Request|array $input, ?int $systemId = null): ?int
    {
        // Support both Request (import) and array (store/update) signatures
        if ($input instanceof Request) {
            if ($input->filled('discipline_id') && $input->discipline_id !== 'new') {
                return (int) $input->discipline_id;
            }
            if ($input->filled('discipline_name') && $systemId) {
                return Discipline::findOrCreateByName($input->discipline_name, $systemId)->id;
            }
            return null;
        }

        // Array from validated store/update data
        $newName = trim($input['new_discipline_name'] ?? '');
        if ($newName !== '') {
            $sid = $systemId ?? (int) ($input['client_system_id'] ?? 0);
            if ($sid > 0) {
                return Discipline::findOrCreateByName($newName, $sid)->id;
            }
        }

        return $input['discipline_id'] ?: null;
    }

    private function parseXmlAuto(string $path, int $systemId, int $userId, string $duplicateAction, ?int $disciplineId): array
    {
        $content = file_get_contents($path);

        if (str_contains($content, 'imsqti_v2') || str_contains($content, 'assessmentItem')) {
            return $this->parseQtiSingleFile($path, $systemId, $userId, $duplicateAction, $disciplineId);
        }

        return $this->parseXml($path, $systemId, $userId, $duplicateAction, $disciplineId);
    }

    private function parseQtiZip(string $path, int $systemId, int $userId, string $duplicateAction, ?int $disciplineId): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return ['error' => 'Não foi possível abrir o arquivo ZIP.'];
        }

        $extractDir = storage_path('app/temp/qti_import_' . uniqid());
        File::ensureDirectoryExists($extractDir);
        $zip->extractTo($extractDir);
        $zip->close();

        // Try to detect discipline from ZIP filename
        $originalName = request()->file('arquivo')->getClientOriginalName();
        if (! $disciplineId && preg_match('/Prova_Facil_(.+)\.zip$/i', $originalName, $m)) {
            $disciplineName = str_replace('_', ' ', trim($m[1]));
            $discipline = Discipline::findOrCreateByName($disciplineName, $systemId);
            $disciplineId = $discipline->id;
        }

        // Find XML files — could be in root or xml/ subfolder
        $xmlFiles = glob($extractDir . '/xml/*.xml') ?: [];
        if ($xmlFiles === []) {
            $xmlFiles = glob($extractDir . '/*.xml') ?: [];
            // Exclude imsmanifest.xml
            $xmlFiles = array_filter($xmlFiles, fn ($f) => basename($f) !== 'imsmanifest.xml');
        }

        if ($xmlFiles === []) {
            // Check one level deeper (ZIP with wrapper folder)
            $subDirs = glob($extractDir . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($subDirs as $subDir) {
                $xmlFiles = glob($subDir . '/xml/*.xml') ?: [];
                if ($xmlFiles !== []) break;
                $found = glob($subDir . '/*.xml') ?: [];
                $found = array_filter($found, fn ($f) => basename($f) !== 'imsmanifest.xml');
                if ($found !== []) { $xmlFiles = $found; break; }
            }
        }

        if ($xmlFiles === []) {
            File::deleteDirectory($extractDir);
            return ['error' => 'Nenhum arquivo XML de questão encontrado no ZIP.'];
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($xmlFiles as $xmlFile) {
            $itemResult = $this->parseQtiSingleFile($xmlFile, $systemId, $userId, $duplicateAction, $disciplineId);

            $created += $itemResult['created'];
            $skipped += $itemResult['skipped'];
            $errors = array_merge($errors, $itemResult['errors']);
        }

        File::deleteDirectory($extractDir);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 50),
            'discipline' => $disciplineId ? Discipline::find($disciplineId)?->name : null,
        ];
    }

    private function parseQtiSingleFile(string $path, int $systemId, int $userId, string $duplicateAction, ?int $disciplineId): array
    {
        libxml_use_internal_errors(true);
        $xmlContent = file_get_contents($path);
        // Remove namespace for easier parsing
        $xmlContent = preg_replace('/xmlns[^=]*="[^"]*"/', '', $xmlContent);
        $xmlContent = preg_replace('/xsi:schemaLocation="[^"]*"/', '', $xmlContent);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            libxml_clear_errors();
            return ['created' => 0, 'skipped' => 0, 'errors' => [basename($path) . ': XML inválido.']];
        }

        $filename = basename($path);

        // Detect correct response(s)
        $correctChoiceIds = [];
        foreach ($xml->responseDeclaration as $rd) {
            if (isset($rd->correctResponse)) {
                foreach ($rd->correctResponse->value as $val) {
                    $correctChoiceIds[] = (string) $val;
                }
            }
        }

        // Extract question text from itemBody
        $bodyContent = '';
        $choices = [];

        if (isset($xml->itemBody)) {
            // Get question text (everything before choiceInteraction)
            foreach ($xml->itemBody->children() as $child) {
                if ($child->getName() === 'div') {
                    $bodyContent .= $this->decodeQtiHtml((string) $child);
                }
            }

            // Get choices from choiceInteraction
            $interaction = $xml->itemBody->choiceInteraction;
            if ($interaction) {
                $order = 1;
                foreach ($interaction->simpleChoice as $sc) {
                    $choiceId = (string) ($sc['identifier'] ?? '');
                    $choiceText = '';
                    foreach ($sc->children() as $child) {
                        $choiceText .= $this->decodeQtiHtml((string) $child);
                    }
                    $choiceText = trim($choiceText);
                    if ($choiceText === '') continue;

                    $choices[] = [
                        'content' => $choiceText,
                        'is_correct' => in_array($choiceId, $correctChoiceIds, true),
                        'order' => $order++,
                    ];
                }
            }
        }

        $bodyContent = trim($bodyContent);
        if ($bodyContent === '') {
            return ['created' => 0, 'skipped' => 0, 'errors' => [$filename . ': enunciado vazio.']];
        }

        if (count($choices) < 2) {
            return ['created' => 0, 'skipped' => 0, 'errors' => [$filename . ': menos de 2 alternativas.']];
        }

        $hasCorrect = collect($choices)->contains('is_correct', true);
        if (! $hasCorrect) {
            return ['created' => 0, 'skipped' => 0, 'errors' => [$filename . ': nenhuma alternativa correta.']];
        }

        // Detect type
        $type = count($choices) === 2 ? 'true_false' : 'multiple_choice';
        $multipleCorrect = collect($choices)->where('is_correct', true)->count() > 1;
        if ($multipleCorrect) {
            $type = 'multiple_answer';
        }

        // Check duplicate
        if ($duplicateAction === 'pular') {
            $exists = Question::where('client_system_id', $systemId)
                ->where('content', $bodyContent)
                ->exists();
            if ($exists) {
                return ['created' => 0, 'skipped' => 1, 'errors' => []];
            }
        }

        try {
            DB::transaction(function () use ($bodyContent, $type, $systemId, $userId, $choices, $disciplineId) {
                $question = Question::create([
                    'client_system_id' => $systemId,
                    'discipline_id' => $disciplineId,
                    'created_by' => $userId,
                    'type' => $type,
                    'content' => $bodyContent,
                    'explanation' => null,
                    'difficulty' => 'medium',
                    'tags' => [],
                    'active' => true,
                    'visibility_scope' => 'system',
                ]);

                foreach ($choices as $choice) {
                    $question->choices()->create($choice);
                }
            });

            return ['created' => 1, 'skipped' => 0, 'errors' => []];
        } catch (\Throwable $e) {
            return ['created' => 0, 'skipped' => 0, 'errors' => [$filename . ': ' . $e->getMessage()]];
        }
    }

    private function decodeQtiHtml(string $raw): string
    {
        // QTI stores HTML-encoded content inside <div> tags: &lt;p&gt;texto&lt;/p&gt;
        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip HTML tags to get plain text
        $text = strip_tags($decoded);
        return trim($text);
    }

    private function parseCsv(string $path, int $systemId, int $userId, string $duplicateAction, ?int $disciplineId = null): array
    {
        $handle = fopen($path, 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, ',');
        if (! $header) {
            fclose($handle);
            return ['error' => 'Arquivo CSV vazio ou inválido.'];
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        if (! in_array('enunciado', $header)) {
            fclose($handle);
            return ['error' => "Coluna obrigatória 'enunciado' não encontrada. Baixe o modelo para ver o formato correto."];
        }

        $created = 0;
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

            $content = $data['enunciado'] ?? '';
            if (empty($content)) {
                $errors[] = "Linha {$row}: enunciado vazio.";
                continue;
            }

            $type = $this->resolveType($data['tipo'] ?? 'multiple_choice');
            $difficulty = $this->resolveDifficulty($data['dificuldade'] ?? 'medium');
            $feedback = trim((string) ($data['feedback'] ?? $data['explicacao'] ?? ''));

            if ($duplicateAction === 'pular') {
                $exists = Question::where('client_system_id', $systemId)
                    ->where('content', $content)
                    ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            $choices = [];
            for ($i = 1; $i <= 5; $i++) {
                $text = $data["alternativa_{$i}"] ?? '';
                if ($text === '') continue;
                $choices[] = [
                    'content' => $text,
                    'is_correct' => (bool) ($data["correta_{$i}"] ?? false),
                    'order' => $i,
                ];
            }

            if (count($choices) < 2) {
                $errors[] = "Linha {$row}: mínimo de 2 alternativas necessário.";
                continue;
            }

            $hasCorrect = collect($choices)->contains('is_correct', true);
            if (! $hasCorrect) {
                $errors[] = "Linha {$row}: nenhuma alternativa marcada como correta.";
                continue;
            }

            try {
                DB::transaction(function () use ($content, $type, $difficulty, $data, $systemId, $userId, $choices, $disciplineId) {
                    $question = Question::create([
                        'client_system_id' => $systemId,
                        'discipline_id' => $disciplineId,
                        'created_by' => $userId,
                        'type' => $type,
                        'content' => $content,
                        'explanation' => $feedback !== '' ? $feedback : null,
                        'difficulty' => $difficulty,
                        'tags' => ! empty($data['tags']) ? array_map('trim', explode(',', $data['tags'])) : [],
                        'active' => true,
                        'visibility_scope' => 'system',
                    ]);

                    foreach ($choices as $choice) {
                        $question->choices()->create($choice);
                    }
                });
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Linha {$row}: erro ao salvar — {$e->getMessage()}";
            }
        }

        fclose($handle);

        return compact('created', 'skipped', 'errors');
    }

    private function parseXml(string $path, int $systemId, int $userId, string $duplicateAction, ?int $disciplineId = null): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);

        if ($xml === false) {
            $xmlError = libxml_get_errors()[0] ?? null;
            libxml_clear_errors();
            return ['error' => 'Arquivo XML inválido.' . ($xmlError ? " {$xmlError->message}" : '')];
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $row = 0;

        foreach ($xml->questao as $q) {
            $row++;

            $content = trim((string) $q->enunciado);
            if (empty($content)) {
                $errors[] = "Questão #{$row}: enunciado vazio.";
                continue;
            }

            $type = $this->resolveType((string) ($q['tipo'] ?? 'multiple_choice'));
            $difficulty = $this->resolveDifficulty((string) ($q['dificuldade'] ?? 'medium'));

            if ($duplicateAction === 'pular') {
                $exists = Question::where('client_system_id', $systemId)
                    ->where('content', $content)
                    ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            $choices = [];
            $order = 1;
            if (isset($q->alternativas)) {
                foreach ($q->alternativas->alternativa as $alt) {
                    $text = trim((string) $alt);
                    if ($text === '') continue;
                    $choices[] = [
                        'content' => $text,
                        'is_correct' => (bool) (int) ($alt['correta'] ?? 0),
                        'order' => $order++,
                    ];
                }
            }

            if (count($choices) < 2) {
                $errors[] = "Questão #{$row}: mínimo de 2 alternativas necessário.";
                continue;
            }

            $hasCorrect = collect($choices)->contains('is_correct', true);
            if (! $hasCorrect) {
                $errors[] = "Questão #{$row}: nenhuma alternativa marcada como correta.";
                continue;
            }

            $tags = trim((string) ($q->tags ?? ''));

            try {
                DB::transaction(function () use ($content, $type, $difficulty, $q, $tags, $systemId, $userId, $choices, $disciplineId) {
                    $question = Question::create([
                        'client_system_id' => $systemId,
                        'discipline_id' => $disciplineId,
                        'created_by' => $userId,
                        'type' => $type,
                        'content' => $content,
                        'explanation' => trim((string) ($q->explicacao ?? '')) ?: null,
                        'difficulty' => $difficulty,
                        'tags' => ! empty($tags) ? array_map('trim', explode(',', $tags)) : [],
                        'active' => true,
                        'visibility_scope' => 'system',
                    ]);

                    foreach ($choices as $choice) {
                        $question->choices()->create($choice);
                    }
                });
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Questão #{$row}: erro ao salvar — {$e->getMessage()}";
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    private function resolveType(string $input): string
    {
        $map = [
            'multiple_choice' => 'multiple_choice',
            'multipla_escolha' => 'multiple_choice',
            'multipla escolha' => 'multiple_choice',
            'true_false' => 'true_false',
            'verdadeiro_falso' => 'true_false',
            'verdadeiro/falso' => 'true_false',
            'multiple_answer' => 'multiple_answer',
            'multiplas_respostas' => 'multiple_answer',
            'multiplas respostas' => 'multiple_answer',
        ];

        return $map[strtolower(trim($input))] ?? 'multiple_choice';
    }

    private function resolveDifficulty(string $input): string
    {
        $map = [
            'easy' => 'easy', 'facil' => 'easy', 'fácil' => 'easy',
            'medium' => 'medium', 'media' => 'medium', 'média' => 'medium',
            'hard' => 'hard', 'dificil' => 'hard', 'difícil' => 'hard',
        ];

        return $map[strtolower(trim($input))] ?? 'medium';
    }

    private function isLockedByPublishedExam(Question $question): bool
    {
        return $question->exams()
            ->whereIn('status', ['published', 'active', 'closed', 'archived'])
            ->exists();
    }

    private function authorizeQuestionAccess(Question $question): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_if($question->client_system_id !== $user->client_system_id, 403);

        $visible = Question::query()
            ->whereKey($question->id)
            ->visibleForUser($user)
            ->exists();

        abort_if(! $visible, 403);
    }

    public function uploadImagem(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        $path = $request->file('image')->store('questoes/imagens', 'public');

        return response()->json([
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
        ]);
    }

}

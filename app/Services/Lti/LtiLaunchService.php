<?php

namespace App\Services\Lti;

use App\Models\Discipline;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LtiLaunchService
{
    public function __construct(
        private readonly LtiOidcService $oidcService,
    ) {}

    /**
     * @return ExamSession|array ExamSession on success, array with 'needs_mapping' on pending resource link
     */
    public function launch(array $input, ?array $launchContext = null): ExamSession|array
    {
        $idToken = trim((string) ($input['id_token'] ?? ''));

        if ($idToken === '') {
            throw new \RuntimeException('id_token nao informado no launch LTI.');
        }

        $unverifiedClaims = $this->oidcService->decodeUnverifiedPayload($idToken);
        $registration = $this->resolveRegistration($unverifiedClaims);
        $claims = json_decode(json_encode($this->oidcService->decodeVerifiedToken($idToken, $registration)), true);

        if (! is_array($claims)) {
            throw new \RuntimeException('Claims verificados do launch LTI estao invalidos.');
        }

        $this->assertExpectedClaims($claims, $registration, $launchContext);

        Log::info('LTI launch claims recebidos', [
            'registration_id' => $registration->id,
            'custom' => $claims['https://purl.imsglobal.org/spec/lti/claim/custom'] ?? [],
            'resource_link' => $claims['https://purl.imsglobal.org/spec/lti/claim/resource_link'] ?? [],
            'context' => $claims['https://purl.imsglobal.org/spec/lti/claim/context'] ?? [],
            'target_link_uri' => $claims['https://purl.imsglobal.org/spec/lti/claim/target_link_uri'] ?? '',
            'ags_endpoint' => $claims['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'] ?? [],
            'launch_context_target' => $launchContext['target_link_uri'] ?? '',
            'email' => $claims['email'] ?? '',
            'sub' => $claims['sub'] ?? '',
            'name' => $claims['name'] ?? '',
            'roles' => $claims['https://purl.imsglobal.org/spec/lti/claim/roles'] ?? [],
        ]);

        $result = $this->resolveResourceLink($registration, $claims, $launchContext);

        // Se retornou array, precisa de mapeamento manual (professor)
        if (is_array($result)) {
            return $result;
        }

        $resourceLink = $result;

        // Associar disciplina do Moodle à prova automaticamente
        $contextClaim = $claims['https://purl.imsglobal.org/spec/lti/claim/context'] ?? [];
        $contextTitle = is_array($contextClaim) ? trim((string) ($contextClaim['title'] ?? '')) : '';
        if ($contextTitle !== '' && $resourceLink->exam) {
            $this->syncExamDiscipline($resourceLink->exam, $contextTitle, $registration->client_system_id);
        }

        $student = $this->resolveStudent($registration, $claims);

        return $this->findOrCreateSession($resourceLink, $student);
    }

    private function resolveRegistration(array $claims): LtiRegistration
    {
        $issuer = trim((string) ($claims['iss'] ?? ''));
        $audience = $claims['aud'] ?? null;
        $clientIds = is_array($audience) ? $audience : [$audience];
        $deploymentId = trim((string) ($claims['https://purl.imsglobal.org/spec/lti/claim/deployment_id'] ?? ''));

        if ($issuer === '' || $clientIds === []) {
            throw new \RuntimeException('Claims obrigatorias do registro LTI nao foram informadas.');
        }

        $query = LtiRegistration::query()
            ->where('issuer', $issuer)
            ->whereIn('client_id', array_filter(array_map(fn ($value) => (string) $value, $clientIds)))
            ->where('active', true);

        if ($deploymentId !== '') {
            $query->where(function ($builder) use ($deploymentId) {
                $builder->where('deployment_id', $deploymentId)
                    ->orWhereNull('deployment_id');
            });
        }

        $registration = $query->orderByDesc('deployment_id')->first();

        if (! $registration) {
            throw new \RuntimeException('Nenhum registro LTI ativo foi encontrado para este launch.');
        }

        return $registration;
    }

    private function assertExpectedClaims(array $claims, LtiRegistration $registration, ?array $launchContext = null): void
    {
        $issuer = (string) ($claims['iss'] ?? '');
        $audience = $claims['aud'] ?? null;
        $deploymentId = (string) ($claims['https://purl.imsglobal.org/spec/lti/claim/deployment_id'] ?? '');
        $nonce = (string) ($claims['nonce'] ?? '');
        $messageType = (string) ($claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? '');
        $ltiVersion = (string) ($claims['https://purl.imsglobal.org/spec/lti/claim/version'] ?? '');

        if ($issuer !== $registration->issuer) {
            throw new \RuntimeException('Issuer do launch LTI nao confere com o registro configurado.');
        }

        $audiences = is_array($audience) ? $audience : [$audience];
        if (! in_array($registration->client_id, $audiences, true)) {
            throw new \RuntimeException('Audience (aud) do launch LTI nao contem o client_id esperado.');
        }

        if ($ltiVersion !== '' && $ltiVersion !== '1.3.0') {
            throw new \RuntimeException('Versao LTI invalida. Esperado 1.3.0, recebido: '.$ltiVersion);
        }

        if ($registration->deployment_id && $deploymentId !== $registration->deployment_id) {
            throw new \RuntimeException('Deployment ID do launch LTI nao confere com o registro configurado.');
        }

        if ($messageType !== 'LtiResourceLinkRequest') {
            throw new \RuntimeException('message_type do launch LTI e invalido.');
        }

        if (is_array($launchContext)) {
            $expectedRegistrationId = (int) ($launchContext['registration_id'] ?? 0);
            $expectedNonce = (string) ($launchContext['nonce'] ?? '');

            if ($expectedRegistrationId > 0 && $registration->id !== $expectedRegistrationId) {
                throw new \RuntimeException('Registro do launch LTI nao confere com o estado OIDC armazenado.');
            }

            if ($expectedNonce !== '' && $nonce !== $expectedNonce) {
                throw new \RuntimeException('Nonce do launch LTI e invalido.');
            }
        }
    }

    private function resolveResourceLink(LtiRegistration $registration, array $claims, ?array $launchContext = null): LtiResourceLink|array
    {
        $resourceLinkClaim = $claims['https://purl.imsglobal.org/spec/lti/claim/resource_link'] ?? [];
        $contextClaim = $claims['https://purl.imsglobal.org/spec/lti/claim/context'] ?? [];
        $customClaim = $claims['https://purl.imsglobal.org/spec/lti/claim/custom'] ?? [];
        $agsClaim = $claims['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'] ?? [];
        $launchPresentationClaim = $claims['https://purl.imsglobal.org/spec/lti/claim/launch_presentation'] ?? [];

        $resourceLinkId = is_array($resourceLinkClaim) ? (string) ($resourceLinkClaim['id'] ?? '') : '';
        $contextId = is_array($contextClaim) ? (string) ($contextClaim['id'] ?? '') : '';
        $contextLabel = is_array($contextClaim) ? (string) ($contextClaim['label'] ?? '') : '';
        $contextTitle = is_array($contextClaim) ? (string) ($contextClaim['title'] ?? '') : '';

        if ($resourceLinkId === '') {
            throw new \RuntimeException('resource_link.id nao informado no launch LTI.');
        }

        $query = LtiResourceLink::query()
            ->where('lti_registration_id', $registration->id)
            ->where('resource_link_id', $resourceLinkId);

        if ($contextId !== '') {
            $query->where(function ($builder) use ($contextId) {
                $builder->where('context_id', $contextId)
                    ->orWhere('context_id', '')
                    ->orWhereNull('context_id');
            });
        }

        $resourceLink = $query->with('exam')->first();

        // Se já existe e tem exam, atualiza AGS endpoints e retorna
        if ($resourceLink && $resourceLink->exam) {
            $this->updateAgsEndpoints($resourceLink, $agsClaim);
            $this->updateLaunchPresentationSettings($resourceLink, $launchPresentationClaim);

            return $resourceLink;
        }

        // Auto-criar resource link: busca exam_id do custom param, target_link_uri ou fallback
        $targetLinkUri = (string) ($claims['https://purl.imsglobal.org/spec/lti/claim/target_link_uri'] ?? '');
        if ($targetLinkUri === '' && is_array($launchContext)) {
            $targetLinkUri = (string) ($launchContext['target_link_uri'] ?? '');
        }
        $examId = $this->resolveExamIdFromClaims($registration, $customClaim, $resourceLinkId, $targetLinkUri);

        if (! $examId) {
            // Verifica se é professor/instrutor — se sim, mostra tela de mapeamento
            $roles = $claims['https://purl.imsglobal.org/spec/lti/claim/roles'] ?? [];
            $isInstructor = $this->hasInstructorRole($roles);

            Log::info('LTI launch: resource link nao mapeado', [
                'registration_id' => $registration->id,
                'resource_link_id' => $resourceLinkId,
                'custom_claims' => $customClaim,
                'is_instructor' => $isInstructor,
                'roles' => $roles,
            ]);

            if ($isInstructor) {
                $lineitemUrl = is_array($agsClaim) ? (string) ($agsClaim['lineitem'] ?? '') : '';
                $lineitemsUrl = is_array($agsClaim) ? (string) ($agsClaim['lineitems'] ?? '') : '';

                return [
                    'needs_mapping' => true,
                    'registration_id' => $registration->id,
                    'resource_link_id' => $resourceLinkId,
                    'context_id' => $contextId,
                    'context_label' => $contextLabel,
                    'context_title' => $contextTitle,
                    'lineitem_url' => $lineitemUrl,
                    'lineitems_url' => $lineitemsUrl,
                    'platform_name' => $registration->platform_name ?? 'Moodle',
                ];
            }

            throw new \RuntimeException(
                'Esta atividade do Moodle ainda nao foi vinculada a uma prova no AvaliaFA. '
                .'Solicite ao professor que acesse a atividade primeiro para configurar o vinculo.'
            );
        }

        $lineitemUrl = is_array($agsClaim) ? (string) ($agsClaim['lineitem'] ?? '') : '';
        $lineitemsUrl = is_array($agsClaim) ? (string) ($agsClaim['lineitems'] ?? '') : '';

        $resourceLink = LtiResourceLink::create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $examId,
            'resource_link_id' => $resourceLinkId,
            'context_id' => $contextId ?: null,
            'context_label' => $contextLabel ?: null,
            'context_title' => $contextTitle ?: null,
            'lineitem_url' => $lineitemUrl ?: null,
            'lineitems_url' => $lineitemsUrl ?: null,
            'settings' => $this->buildLaunchPresentationSettings($launchPresentationClaim),
        ]);

        Log::info('LTI ResourceLink auto-criado', [
            'resource_link_id' => $resourceLink->id,
            'exam_id' => $examId,
            'lti_resource_link_id' => $resourceLinkId,
            'registration_id' => $registration->id,
        ]);

        return $resourceLink->load('exam');
    }

    private function resolveExamIdFromClaims(LtiRegistration $registration, array $customClaim, string $resourceLinkId, string $targetLinkUri = ''): ?int
    {
        // 1. Custom param "exam_id" (configurado no Moodle: exam_id=123)
        $examId = (int) ($customClaim['exam_id'] ?? $customClaim['examid'] ?? 0);

        if ($examId > 0) {
            $exam = Exam::where('id', $examId)
                ->where('client_system_id', $registration->client_system_id)
                ->first();

            if ($exam) {
                return $exam->id;
            }
        }

        // 2. Extrair exam_id do target_link_uri (ex: /lti/launch?exam_id=5)
        if ($targetLinkUri !== '') {
            $parsedQuery = [];
            $queryString = parse_url($targetLinkUri, PHP_URL_QUERY);
            if ($queryString) {
                parse_str($queryString, $parsedQuery);
            }

            $uriExamId = (int) ($parsedQuery['exam_id'] ?? $parsedQuery['examid'] ?? 0);

            if ($uriExamId > 0) {
                $exam = Exam::where('id', $uriExamId)
                    ->where('client_system_id', $registration->client_system_id)
                    ->first();

                if ($exam) {
                    Log::info('LTI auto-mapeamento via target_link_uri', [
                        'exam_id' => $exam->id,
                        'target_link_uri' => $targetLinkUri,
                    ]);

                    return $exam->id;
                }
            }
        }

        // 3. Fallback: se o client_system tem apenas 1 prova ativa/publicada, usa ela
        $activeExams = Exam::where('client_system_id', $registration->client_system_id)
            ->whereIn('status', ['published', 'active'])
            ->limit(2)
            ->get();

        if ($activeExams->count() === 1) {
            Log::info('LTI auto-mapeamento: unica prova ativa encontrada', [
                'exam_id' => $activeExams->first()->id,
                'registration_id' => $registration->id,
            ]);

            return $activeExams->first()->id;
        }

        return null;
    }

    private function hasInstructorRole(array $roles): bool
    {
        $instructorPatterns = [
            'Instructor',
            'ContentDeveloper',
            'Administrator',
            'TeachingAssistant',
            'Mentor',
            '#Instructor',
            '#ContentDeveloper',
            '#Administrator',
        ];

        foreach ($roles as $role) {
            foreach ($instructorPatterns as $pattern) {
                if (stripos((string) $role, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cria o resource link a partir do mapeamento feito pelo professor.
     */
    public function createResourceLinkMapping(int $registrationId, string $resourceLinkId, int $examId, array $extra = []): LtiResourceLink
    {
        $registration = LtiRegistration::findOrFail($registrationId);

        $exam = Exam::where('id', $examId)
            ->where('client_system_id', $registration->client_system_id)
            ->firstOrFail();

        $resourceLink = LtiResourceLink::create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => $resourceLinkId,
            'context_id' => $this->nullableString($extra['context_id'] ?? null),
            'context_label' => $this->nullableString($extra['context_label'] ?? null),
            'context_title' => $this->nullableString($extra['context_title'] ?? null),
            'lineitem_url' => $this->nullableString($extra['lineitem_url'] ?? null),
            'lineitems_url' => $this->nullableString($extra['lineitems_url'] ?? null),
        ]);

        Log::info('LTI ResourceLink mapeado manualmente por professor', [
            'resource_link_db_id' => $resourceLink->id,
            'exam_id' => $exam->id,
            'resource_link_id' => $resourceLinkId,
            'registration_id' => $registration->id,
        ]);

        // Associar disciplina do Moodle à prova
        $contextTitle = trim((string) ($extra['context_title'] ?? ''));
        if ($contextTitle !== '') {
            $this->syncExamDiscipline($exam, $contextTitle, $registration->client_system_id);
        }

        return $resourceLink;
    }

    private function updateAgsEndpoints(LtiResourceLink $resourceLink, $agsClaim): void
    {
        if (! is_array($agsClaim)) {
            return;
        }

        $lineitemUrl = (string) ($agsClaim['lineitem'] ?? '');
        $lineitemsUrl = (string) ($agsClaim['lineitems'] ?? '');
        $changed = false;

        if ($lineitemUrl !== '' && $resourceLink->lineitem_url !== $lineitemUrl) {
            $resourceLink->lineitem_url = $lineitemUrl;
            $changed = true;
        }

        if ($lineitemsUrl !== '' && $resourceLink->lineitems_url !== $lineitemsUrl) {
            $resourceLink->lineitems_url = $lineitemsUrl;
            $changed = true;
        }

        if ($changed) {
            $resourceLink->save();
        }
    }

    private function updateLaunchPresentationSettings(LtiResourceLink $resourceLink, mixed $launchPresentationClaim): void
    {
        if (! is_array($launchPresentationClaim)) {
            return;
        }

        $returnUrl = trim((string) ($launchPresentationClaim['return_url'] ?? ''));

        if ($returnUrl === '') {
            return;
        }

        $settings = is_array($resourceLink->settings) ? $resourceLink->settings : [];

        if (($settings['launch_presentation']['return_url'] ?? null) === $returnUrl) {
            return;
        }

        $settings['launch_presentation']['return_url'] = $returnUrl;
        $resourceLink->update(['settings' => $settings]);
    }

    private function buildLaunchPresentationSettings(mixed $launchPresentationClaim): array
    {
        if (! is_array($launchPresentationClaim)) {
            return [];
        }

        $returnUrl = trim((string) ($launchPresentationClaim['return_url'] ?? ''));

        if ($returnUrl === '') {
            return [];
        }

        return [
            'launch_presentation' => [
                'return_url' => $returnUrl,
            ],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function resolveStudent(LtiRegistration $registration, array $claims): User
    {
        $email = trim((string) ($claims['email'] ?? ''));
        $subject = trim((string) ($claims['sub'] ?? ''));
        $givenName = trim((string) ($claims['given_name'] ?? ''));
        $familyName = trim((string) ($claims['family_name'] ?? ''));
        $fullName = trim((string) ($claims['name'] ?? ''));

        if ($email === '' && $subject === '') {
            throw new \RuntimeException('Email ou sub do estudante nao informados no launch LTI.');
        }

        $query = User::query()
            ->where('client_system_id', $registration->client_system_id)
            ->where('role', 'student')
            ->where(function ($builder) use ($email, $subject) {
                if ($email !== '') {
                    $builder->orWhere('email', $email);
                }

                if ($subject !== '') {
                    $builder->orWhere('external_id', $subject)
                        ->orWhere('moodle_user_id', $subject);
                }
            });

        $student = $query->first();

        if ($student) {
            // Atualiza moodle_user_id se ainda não está preenchido
            if ($subject !== '' && empty($student->moodle_user_id)) {
                $student->moodle_user_id = $subject;
                $student->save();
            }

            return $student;
        }

        // Auto-criar estudante a partir dos claims LTI
        if ($email === '') {
            throw new \RuntimeException('Email do estudante e obrigatorio para auto-cadastro via LTI.');
        }

        $name = $fullName;
        if ($name === '' && ($givenName !== '' || $familyName !== '')) {
            $name = trim($givenName.' '.$familyName);
        }
        if ($name === '') {
            $name = explode('@', $email)[0];
        }

        $student = User::create([
            'client_system_id' => $registration->client_system_id,
            'role' => 'student',
            'name' => $name,
            'first_name' => $givenName ?: null,
            'last_name' => $familyName ?: null,
            'email' => $email,
            'external_id' => $subject ?: null,
            'moodle_user_id' => $subject ?: null,
            'password' => bcrypt(Str::random(32)),
            'active' => true,
        ]);

        Log::info('Estudante auto-criado via LTI launch', [
            'student_id' => $student->id,
            'email' => $email,
            'moodle_user_id' => $subject,
            'registration_id' => $registration->id,
        ]);

        return $student;
    }

    private function syncExamDiscipline(Exam $exam, string $contextTitle, int $clientSystemId): void
    {
        if ($exam->discipline_id) {
            return;
        }

        try {
            $discipline = Discipline::findOrCreateByName($contextTitle, $clientSystemId);
            $exam->update(['discipline_id' => $discipline->id]);

            Log::info('Disciplina associada automaticamente via LTI context_title', [
                'exam_id' => $exam->id,
                'discipline_id' => $discipline->id,
                'discipline_name' => $discipline->name,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao associar disciplina via LTI', [
                'exam_id' => $exam->id,
                'context_title' => $contextTitle,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findOrCreateSession(LtiResourceLink $resourceLink, User $student): ExamSession
    {
        // 1. Sessão ativa (pending/in_progress) — retomar
        $activeSession = ExamSession::query()
            ->where('exam_id', $resourceLink->exam_id)
            ->where('student_id', $student->id)
            ->where('launch_source', 'lti')
            ->where('lti_registration_id', $resourceLink->lti_registration_id)
            ->where('lti_resource_link_id', $resourceLink->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest('id')
            ->first();

        if ($activeSession) {
            return $activeSession->fresh(['exam', 'student']);
        }

        // 2. Sessão já concluída — retornar a mais recente para exibir resultado
        $gradedSession = ExamSession::query()
            ->where('exam_id', $resourceLink->exam_id)
            ->where('student_id', $student->id)
            ->where('launch_source', 'lti')
            ->where('lti_registration_id', $resourceLink->lti_registration_id)
            ->where('lti_resource_link_id', $resourceLink->id)
            ->whereIn('status', ['graded', 'submitted'])
            ->latest('id')
            ->first();

        if ($gradedSession) {
            return $gradedSession->fresh(['exam', 'student']);
        }

        // 3. Nenhuma sessão — criar nova
        $attemptNumber = (int) ExamSession::query()
            ->where('exam_id', $resourceLink->exam_id)
            ->where('student_id', $student->id)
            ->max('attempt_number');

        $session = ExamSession::query()->create([
            'exam_id' => $resourceLink->exam_id,
            'student_id' => $student->id,
            'launch_source' => 'lti',
            'lti_registration_id' => $resourceLink->lti_registration_id,
            'lti_resource_link_id' => $resourceLink->id,
            'attempt_number' => $attemptNumber + 1,
            'status' => 'pending',
        ]);

        return $session->fresh(['exam', 'student']);
    }
}

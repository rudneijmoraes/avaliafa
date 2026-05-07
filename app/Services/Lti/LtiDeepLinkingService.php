<?php

namespace App\Services\Lti;

use App\Models\Exam;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\User;

class LtiDeepLinkingService
{
    public function mapResourceLink(User $user, array $input): LtiResourceLink
    {
        $registrationId = (int) ($input['lti_registration_id'] ?? 0);
        $examId = (int) ($input['exam_id'] ?? 0);
        $resourceLinkId = trim((string) ($input['resource_link_id'] ?? ''));

        if ($registrationId <= 0 || $examId <= 0 || $resourceLinkId === '') {
            throw new \RuntimeException('Registro LTI, prova e resource_link_id sao obrigatorios.');
        }

        $registration = LtiRegistration::query()->findOrFail($registrationId);
        $exam = Exam::query()->findOrFail($examId);

        if (! $user->isSuperAdmin() && $user->client_system_id !== $exam->client_system_id) {
            throw new \RuntimeException('Voce nao tem permissao para vincular esta prova.');
        }

        if ((int) $registration->client_system_id !== (int) $exam->client_system_id) {
            throw new \RuntimeException('O registro LTI e a prova precisam pertencer ao mesmo sistema.');
        }

        return LtiResourceLink::query()->updateOrCreate(
            [
                'lti_registration_id' => $registration->id,
                'resource_link_id' => $resourceLinkId,
            ],
            [
                'exam_id' => $exam->id,
                'context_id' => $this->nullableString($input['context_id'] ?? null),
                'context_label' => $this->nullableString($input['context_label'] ?? null),
                'context_title' => $this->nullableString($input['context_title'] ?? null),
                'lineitem_url' => $this->nullableString($input['lineitem_url'] ?? null),
                'lineitems_url' => $this->nullableString($input['lineitems_url'] ?? null),
            ]
        );
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

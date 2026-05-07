<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\Question;
use App\Models\SimuladoRegistration;
use Illuminate\Support\Collection;

class SimuladoReviewPdfService
{
    public function build(SimuladoRegistration $registration): array
    {
        $registration->loadMissing(['simulado.exam.questions.choices', 'examSession.answers']);

        $session = $registration->examSession;
        $exam = $registration->simulado?->exam;

        if (! $session || ! $exam) {
            return [
                'questions' => collect(),
                'correct_count' => 0,
                'wrong_count' => 0,
                'total_count' => 0,
                'percentage' => 0,
            ];
        }

        $answers = $session->answers->keyBy('question_id');
        $questionIds = $this->resolveQuestionOrder($session, $exam->questions);
        $questionsById = $exam->questions->keyBy('id');

        $questions = $questionIds
            ->map(function (int $questionId) use ($answers, $questionsById, $session) {
                /** @var Question|null $question */
                $question = $questionsById->get($questionId);

                if (! $question) {
                    return null;
                }

                $answer = $answers->get($questionId);
                $orderedChoices = $this->resolveChoiceOrder($question, $session);
                $selectedChoiceIds = collect($answer?->choice_ids ?? [])
                    ->push($answer?->choice_id)
                    ->filter(fn ($choiceId) => filled($choiceId))
                    ->map(fn ($choiceId) => (int) $choiceId)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => $question->id,
                    'content' => $question->content,
                    'explanation' => $question->explanation,
                    'type' => $question->type,
                    'choices' => $orderedChoices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'content' => $choice->content,
                        'is_correct' => (bool) $choice->is_correct,
                    ])->values()->all(),
                    'answer' => $answer ? [
                        'selected_choice_id' => $answer->choice_id,
                        'selected_choice_ids' => $selectedChoiceIds,
                        'selected_text' => $answer->text_answer,
                        'order_answer' => $answer->order_answer,
                        'is_correct' => (bool) $answer->is_correct,
                        'score' => $answer->score,
                        'feedback' => $answer->feedback,
                    ] : null,
                ];
            })
            ->filter()
            ->values();

        $correctCount = $questions
            ->filter(fn (array $question) => (bool) data_get($question, 'answer.is_correct', false))
            ->count();

        $totalCount = $questions->count();
        $wrongCount = max(0, $totalCount - $correctCount);
        $percentage = $totalCount > 0 ? round(($correctCount / $totalCount) * 100, 1) : 0;

        return [
            'questions' => $questions,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'total_count' => $totalCount,
            'percentage' => $percentage,
        ];
    }

    private function resolveQuestionOrder(ExamSession $session, Collection $questions): Collection
    {
        $orderedIds = collect($session->question_order ?? [])
            ->map(fn ($questionId) => (int) $questionId)
            ->filter(fn ($questionId) => $questionId > 0)
            ->values();

        if ($orderedIds->isNotEmpty()) {
            $remainingIds = $questions->pluck('id')
                ->map(fn ($questionId) => (int) $questionId)
                ->filter(fn ($questionId) => ! $orderedIds->contains($questionId));

            return $orderedIds->concat($remainingIds)->values();
        }

        return $questions->pluck('id')
            ->map(fn ($questionId) => (int) $questionId)
            ->values();
    }

    private function resolveChoiceOrder(Question $question, ExamSession $session): Collection
    {
        $choiceOrder = $session->choice_order ?? [];
        $questionChoiceOrder = collect($choiceOrder[$question->id] ?? [])
            ->map(fn ($choiceId) => (int) $choiceId)
            ->filter(fn ($choiceId) => $choiceId > 0)
            ->values();

        if ($questionChoiceOrder->isEmpty()) {
            return $question->choices->values();
        }

        $choicesById = $question->choices->keyBy('id');

        $orderedChoices = $questionChoiceOrder
            ->map(fn ($choiceId) => $choicesById->get($choiceId))
            ->filter();

        $remainingChoices = $question->choices
            ->filter(fn ($choice) => ! $questionChoiceOrder->contains((int) $choice->id));

        return $orderedChoices->concat($remainingChoices)->values();
    }
}

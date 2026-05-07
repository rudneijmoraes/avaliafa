<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use Illuminate\Support\Collection;

class SmartShuffleService
{
    /**
     * Build randomized question order for a session.
     * Respects difficulty distribution: N easy + M medium + P hard.
     */
    public function buildQuestionOrder(Exam $exam, ExamSession $session): array
    {
        $exam->loadMissing([
            'questions',
            'blocks' => fn ($query) => $query
                ->with([
                    'examQuestions' => fn ($blockQuestions) => $blockQuestions
                        ->with('question')
                        ->orderBy('order'),
                ])
                ->orderBy('order'),
        ]);

        if ($exam->blocks->isNotEmpty()) {
            return $this->buildQuestionOrderByBlocks($exam);
        }

        $questions = $exam->questions;

        if (! $exam->shuffle_questions) {
            return $questions->pluck('id')->toArray();
        }

        $settings = $exam->settings ?? [];

        // If exam has difficulty quotas configured, apply smart shuffle
        if (isset($settings['difficulty_quota'])) {
            $ordered = $this->applyDifficultyQuota($questions, $settings['difficulty_quota']);
        } else {
            $ordered = $questions->shuffle();
        }

        return $ordered->pluck('id')->toArray();
    }

    /**
     * Build randomized choice order per question.
     * Returns: ['question_id' => [choice_id, ...], ...]
     */
    public function buildChoiceOrder(Exam $exam, array $questionIds): array
    {
        if (! $exam->shuffle_choices) {
            return [];
        }

        $choiceOrder = [];

        $questions = $exam->questions()
            ->with('choices')
            ->whereIn('questions.id', $questionIds)
            ->get()
            ->keyBy('id');

        foreach ($questionIds as $qId) {
            $question = $questions[$qId] ?? null;
            if (! $question) {
                continue;
            }

            // Preserve binary statement alternatives in their authored order.
            if ($this->shouldPreserveChoiceOrder($question)) {
                $choiceOrder[$qId] = $question->choices->pluck('id')->toArray();
            } else {
                $choiceOrder[$qId] = $question->choices->shuffle()->pluck('id')->toArray();
            }
        }

        return $choiceOrder;
    }

    private function shouldPreserveChoiceOrder(Question $question): bool
    {
        if ($question->type === 'true_false') {
            return true;
        }

        $labels = $question->choices
            ->pluck('content')
            ->map(fn ($content) => mb_strtolower(trim((string) $content)))
            ->values();

        if ($labels->count() !== 2) {
            return false;
        }

        $uniqueLabels = $labels->unique()->values()->all();
        sort($uniqueLabels);

        return $uniqueLabels === ['certo', 'errado']
            || $uniqueLabels === ['falso', 'verdadeiro'];
    }

    private function applyDifficultyQuota(Collection $questions, array $quota): Collection
    {
        $byDifficulty = $questions->groupBy('difficulty');

        $easy = ($byDifficulty['easy'] ?? collect())->shuffle()->take($quota['easy'] ?? PHP_INT_MAX);
        $medium = ($byDifficulty['medium'] ?? collect())->shuffle()->take($quota['medium'] ?? PHP_INT_MAX);
        $hard = ($byDifficulty['hard'] ?? collect())->shuffle()->take($quota['hard'] ?? PHP_INT_MAX);

        // Interleave: easy → medium → hard pattern
        return $easy->concat($medium)->concat($hard)->shuffle();
    }

    private function buildQuestionOrderByBlocks(Exam $exam): array
    {
        return $exam->blocks
            ->sortBy('order')
            ->flatMap(function ($block) use ($exam) {
                $questions = $block->examQuestions
                    ->sortBy('order')
                    ->map(fn ($examQuestion) => $examQuestion->question)
                    ->filter()
                    ->values();

                if ($questions->isEmpty()) {
                    return [];
                }

                if (! $exam->shuffle_questions) {
                    return $questions->pluck('id')->all();
                }

                return $questions->shuffle()->pluck('id')->all();
            })
            ->values()
            ->all();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Services\SmartShuffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartShuffleBinaryChoiceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_binary_certo_errado_choices_keep_authored_order_even_when_shuffle_choices_is_enabled(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Binario',
            'slug' => 'sistema-binario',
            'client_id' => 'sistema-binario',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova Binaria',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => true,
            'passing_score' => 6,
            'settings' => [],
        ]);

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Questao binaria',
            'difficulty' => 'easy',
            'active' => true,
        ]);

        $choiceCerto = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Certo',
            'is_correct' => false,
            'order' => 1,
        ]);

        $choiceErrado = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Errado',
            'is_correct' => true,
            'order' => 2,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 1]);

        $order = app(SmartShuffleService::class)->buildChoiceOrder($exam, [$question->id]);

        $this->assertSame([$choiceCerto->id, $choiceErrado->id], $order[$question->id]);
    }
}

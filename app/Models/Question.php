<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'questions';

    protected $fillable = [
        'client_system_id',
        'discipline_id',
        'created_by',
        'type',
        'content',
        'explanation',
        'difficulty',
        'tags',
        'version',
        'active',
        'owner_department',
        'visibility_scope',
    ];

    protected $casts = [
        'version' => 'integer',
        'tags' => 'array',
        'active' => 'boolean',
    ];

    public function clientSystem()
    {
        return $this->belongsTo(ClientSystem::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function choices()
    {
        return $this->hasMany(Choice::class)->orderBy('order');
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot('order', 'weight');
    }

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function blocks()
    {
        return $this->belongsToMany(ExamBlock::class, 'exam_questions', 'question_id', 'exam_block_id')
            ->withPivot(['exam_id', 'order', 'weight']);
    }

    public function isObjective(): bool
    {
        return in_array($this->type, ['multiple_choice', 'true_false', 'multiple_answer', 'ordering']);
    }

    public function correctChoices()
    {
        return $this->choices()->where('is_correct', true);
    }

    public function scopeVisibleForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $userDepartment = trim((string) ($user->department ?? ''));

        return $query->where(function (Builder $q) use ($user, $userDepartment) {
            $q->where('visibility_scope', 'global')
                ->orWhere('visibility_scope', 'system')
                ->orWhere(function (Builder $private) use ($user) {
                    $private->where('visibility_scope', 'private')
                        ->where('created_by', $user->id);
                })
                ->orWhere(function (Builder $department) use ($user, $userDepartment) {
                    $department->where('visibility_scope', 'department')
                        ->where(function (Builder $rule) use ($user, $userDepartment) {
                            $rule->where('created_by', $user->id)
                                ->orWhereNull('owner_department');

                            if ($userDepartment !== '') {
                                $rule->orWhere('owner_department', $userDepartment);
                            }
                        });
                });
        });
    }
}

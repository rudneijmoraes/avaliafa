<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exam_id',
        'title',
        'base_text',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class, 'exam_block_id')->orderBy('order');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_block_id', 'question_id')
            ->withPivot(['exam_id', 'order', 'weight']);
    }
}

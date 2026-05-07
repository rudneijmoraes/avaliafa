<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $table = 'answers';

    protected $fillable = [
        'session_id',
        'question_id',
        'choice_id',
        'choice_ids',
        'text_answer',
        'order_answer',
        'is_correct',
        'score',
        'feedback',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'question_id' => 'integer',
        'choice_id' => 'integer',
        'choice_ids' => 'array',
        'order_answer' => 'array',
        'is_correct' => 'boolean',
        'score' => 'decimal:2',
    ];

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function choice()
    {
        return $this->belongsTo(Choice::class);
    }
}

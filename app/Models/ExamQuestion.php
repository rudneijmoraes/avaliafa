<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'exam_block_id',
        'question_id',
        'order',
        'weight',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examBlock()
    {
        return $this->belongsTo(ExamBlock::class, 'exam_block_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

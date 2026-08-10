<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['question_id', 'text', 'is_correct', 'is_active'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
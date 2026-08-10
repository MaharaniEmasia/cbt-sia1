<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'is_active'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
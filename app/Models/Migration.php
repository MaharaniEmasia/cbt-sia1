<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Migration extends Model
{
    protected $table = 'migrations';
    public $timestamps = false; // Tabel bawaan migrations tidak memiliki kolom created_at/updated_at

    protected $fillable = [
        'migration',
        'batch',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KeyValue extends Model
{
    use SoftDeletes;
    
    protected $table = 'key_values';
    protected $fillable = ['key', 'values', 'timestamp'];
    protected $casts = [
        'values' => 'json',
    ];
}

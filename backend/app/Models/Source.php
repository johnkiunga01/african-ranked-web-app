<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $fillable = ['title', 'publisher', 'url', 'published_at', 'accessed_at'];

    protected $casts = ['published_at' => 'date', 'accessed_at' => 'date'];
}

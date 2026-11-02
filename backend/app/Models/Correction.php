<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Correction extends Model
{
    protected $fillable = ['article_id', 'summary', 'corrected_at'];

    protected $casts = ['corrected_at' => 'datetime'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}

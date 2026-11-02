<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'entity_id', 'position', 'score_total', 'scores', 'blurb'];

    protected $casts = ['scores' => 'array', 'score_total' => 'float'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}

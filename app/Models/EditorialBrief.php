<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EditorialBrief extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['plan' => 'array'];
    }

    public function article(): HasOne
    {
        return $this->hasOne(Article::class, 'slug', 'slug');
    }

    public function instructions(): string
    {
        $text = "Editorial plan {$this->plan_id}. Source: {$this->plan['file']}, {$this->plan['sheet']}, row {$this->plan['row']}.\n";
        foreach ($this->plan['fields'] as $name => $value) {
            $text .= $name.': '.$value."\n";
        }

        return $text."\nPlan keywords, difficulty and monetization are hypotheses, not measured demand, verified claims or commercial relationships. Research and source verification are required before drafting. Never publish automatically.";
    }
}

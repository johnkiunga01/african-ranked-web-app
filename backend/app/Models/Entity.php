<?php

namespace App\Models;

use App\Services\CanonicalUrl;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $fillable = ['type', 'slug', 'name', 'country_code', 'parent_id', 'attributes', 'same_as', 'verified_at'];

    protected $casts = [
        'attributes' => 'array',
        'same_as' => 'array',
        'verified_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function url(): string
    {
        return CanonicalUrl::to('/e/'.$this->slug);
    }
}

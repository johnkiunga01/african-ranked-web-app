<?php

namespace App\Models;

use App\Services\CanonicalUrl;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = ['name', 'slug', 'bio', 'credentials', 'profile_url', 'is_reviewer'];

    protected $casts = ['is_reviewer' => 'boolean'];

    public function url(): string
    {
        return CanonicalUrl::to('/authors/'.$this->slug);
    }
}

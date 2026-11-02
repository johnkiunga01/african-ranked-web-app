<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePageRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['body' => 'array', 'in_footer' => 'boolean', 'published_at' => 'datetime'];
    }
}

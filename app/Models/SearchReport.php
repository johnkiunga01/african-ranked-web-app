<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rows' => 'array', 'period_start' => 'date', 'period_end' => 'date'];
    }
}

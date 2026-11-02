<?php

namespace App\Models;

use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Model;

class StaffInvitation extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['role' => StaffRole::class, 'expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }
}

<?php

namespace App\Enums;

enum StaffRole: string
{
    case Author = 'author';
    case SuperAdmin = 'super_admin';
}

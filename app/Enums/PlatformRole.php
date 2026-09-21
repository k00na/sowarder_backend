<?php

namespace App\Enums;

enum PlatformRole: string
{
    case Owner = 'owner';
    case SuperAdmin = 'super_admin';
}

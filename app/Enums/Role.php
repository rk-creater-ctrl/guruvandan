<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Student = 'student';
    case Teacher = 'teacher';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}

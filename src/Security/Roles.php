<?php

namespace App\Security;

abstract class Roles
{
    public const ADMIN = 'ROLE_ADMIN';
    public const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public static function getRoles(): array
    {
        return [
            'Super Administrator' => static::SUPER_ADMIN,
            'Administrator' => static::ADMIN,
        ];
    }
}

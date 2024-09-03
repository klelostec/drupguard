<?php

namespace App\Security;

abstract class Roles {
    const ADMIN = 'ROLE_ADMIN';
    const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public static function getRoles(): array {
        return [
            'Super Administrator' => static::SUPER_ADMIN,
            'Administrator' => static::ADMIN
        ];
    }
}
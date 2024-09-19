<?php

namespace App\Security;

abstract class ProjectRoles {
    const OWNER = 'PROJECT_OWNER';
    const MAINTAINER = 'PROJECT_MAINTAINER';
    const USER = 'PROJECT_USER';

    public static function getRoles(): array {
        return [
            'Owner' => static::OWNER,
            'Maintainer' => static::MAINTAINER,
            'User' => static::USER
        ];
    }
}
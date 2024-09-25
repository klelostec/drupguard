<?php

namespace App\Security;

abstract class ProjectRoles
{
    public const OWNER = 'PROJECT_OWNER';
    public const MAINTAINER = 'PROJECT_MAINTAINER';
    public const USER = 'PROJECT_USER';

    public static function getRoles(): array
    {
        return [
            'Owner' => static::OWNER,
            'Maintainer' => static::MAINTAINER,
            'User' => static::USER,
        ];
    }
}

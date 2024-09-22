<?php

namespace App\Service;

class SourcePluginManager {
    const LOCAL = 'local';
    const GIT = 'git';

    public static function getTypes() :array {
        return [
            'Local' => static::LOCAL,
            'Git' => static::GIT
        ];
    }

}
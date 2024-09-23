<?php

namespace App\Plugin\Source;

class SourcePluginManager {
    const LOCAL = 'local';
    const GIT = 'git';

    public static function getTypes() :array {
        return [
            '-- Choose --' => '',
            'Local' => static::LOCAL,
            'Git' => static::GIT
        ];
    }

}
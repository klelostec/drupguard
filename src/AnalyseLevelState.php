<?php

namespace App;

use function Symfony\Component\Translation\t;

enum AnalyseLevelState: int
{
    case UNKNOWN = -1;
    case NONE = 0;
    case FAILURE = 1;
    case SECURITY = 2;
    case DANGER = 3;
    case WARNING = 4;
    case SUCCESS = 5;

    public function getColor(): string {
        return match($this) {
            self::UNKNOWN => '#f8f398',
            self::NONE => '#d9dad7',
            self::FAILURE, self::SECURITY => '#e46161',
            self::DANGER, self::WARNING => '#f1b963',
            self::SUCCESS => '#cbf078',
        };
    }

    public function getLabel(): string {
        return match($this) {
            self::UNKNOWN => t('Unknown'),
            self::NONE => t('None'),
            self::FAILURE => t('Failure'),
            self::SECURITY => t('Security'),
            self::DANGER => t('Danger'),
            self::WARNING => t('Warning'),
            self::SUCCESS => t('Success'),
        };
    }
}

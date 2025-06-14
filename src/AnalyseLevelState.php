<?php

namespace App;

enum AnalyseLevelState: int
{
    case UNKNOWN = -1;
    case NONE = 0;
    case FAILURE = 1;
    case SECURITY = 2;
    case DANGER = 3;
    case WARNING = 4;
    case SUCCESS = 5;

    public static function getColor(self $state): string {
        switch ($state) {
            case self::NONE:
                return '#d9dad7';
            case self::FAILURE:
            case self::SECURITY:
                return '#e46161';
            case self::DANGER:
            case self::WARNING:
                return '#f1b963';
            case self::SUCCESS:
                return '#cbf078';
            default:
            case self::UNKNOWN:
                return '#f8f398';
        }
    }
}

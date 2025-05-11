<?php

namespace App;

enum AnalyseLevelState: int
{
    case NONE = 0;
    case FAILURE = 1;
    case SECURITY = 2;
    case DANGER = 3;
    case WARNING = 4;
    case SUCCESS = 5;
}

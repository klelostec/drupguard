<?php

namespace App;

enum AnalyseLevelState: int
{
    case NONE = 0;
    case FAILURE = 1;
    case SECURITY = 2;
    case WARNING = 3;
    case SUCCESS = 4;
}

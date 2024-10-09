<?php

namespace App;

enum ProjectEmailLevelState: int {
    case NONE = 0;
    case FAILURE = 1;
    case SUCCESS = 2;
    case WARNING = 3;
    case ERRORS = 4;
    case SECURITY = 5;
}
<?php

namespace App;

enum ProjectState: int {
    case IDLE = 0;
    case PENDING = 1;
    case SOURCING = 2;
    case BUILDING = 3;
    case ANALYSING = 4;
}
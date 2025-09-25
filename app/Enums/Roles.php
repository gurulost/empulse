<?php

namespace App\Enums;

enum Role: int {
    case ADMIN = 0;
    case MANAGER = 1;
    case CHIEF = 2;
    case TEAMLEAD = 3;
    case EMPLOYEE = 4;
}

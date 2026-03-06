<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('survey:waves:schedule')
    ->everyMinute()
    ->withoutOverlapping(10);

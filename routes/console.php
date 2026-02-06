<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('email:link')->monthlyOn(6, '10:00');
Schedule::command('survey:waves:schedule')->everyMinute();

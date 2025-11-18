<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected $commands = [
        \App\Console\Commands\SendLink::class,
        \App\Console\Commands\ImportSurvey::class,
        \App\Console\Commands\ScheduleSurveyWaves::class,
    ];
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('email:link')->monthlyOn(6, '10:00');
        $schedule->command('survey:waves:schedule')->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

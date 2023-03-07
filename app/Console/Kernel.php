<?php

namespace App\Console;

use App\Jobs\RemoveFeedsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\ShortSchedule\ShortSchedule;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Remove old feeds every thirty minutes
        $schedule->job(new RemoveFeedsJob)->everyThirtyMinutes();
    }

    protected function shortSchedule(ShortSchedule $shortSchedule)
    {   
        // refresh feeds every 30 seconds
        $shortSchedule->command('feeds:refresh')->everySeconds(30)->withoutOverlapping();
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

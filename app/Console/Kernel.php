<?php

namespace App\Console;

use App\Console\Commands\RetryAirtime;
use App\Setting;
use App\Console\Commands\RetryData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        RetryData::class,
        RetryAirtime::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

       
        //$schedule->command('retry:data 5 mtn')->everyTenMinutes();
        
        $schedule->command('telescope:prune --hours=2')->hourly();
        $schedule->command('telescope:clear')->hourly();
        $schedule->exec('chown -R www-data:www-data /var/www/Zealvend/storage/logs')->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}

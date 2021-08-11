<?php

namespace App\Console;

use App\Console\Commands\AddCryptoCurrenciesFromCoinMarketCapAPI;
use App\Console\Commands\DumpNotifications;
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
        AddCryptoCurrenciesFromCoinMarketCapAPI::class,
        DumpNotifications::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('crypto_currencies:add')->runInBackground()->hourly();

        $keep = config('general.notifications.keep');
        $schedule->command("notifications:dump --keep={$keep}")->runInBackground()->monthly();
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

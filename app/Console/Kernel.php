<?php

namespace App\Console;

use App\Console\Commands\CheckArchivePaymentDetails;
use App\Console\Commands\CheckExpiredPaymentDetails;
use App\Console\Commands\DumpNotifications;
use App\Console\Commands\UpdateCryptoCurrenciesPricesFromCoinMarketCapAPI;
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
        UpdateCryptoCurrenciesPricesFromCoinMarketCapAPI::class,
        DumpNotifications::class,
        CheckExpiredPaymentDetails::class,
        CheckArchivePaymentDetails::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('crypto_currencies:update')->runInBackground()->everyMinute();

        $keep = config('general.notifications.keep');
        $schedule->command("notifications:dump --keep={$keep}")->runInBackground()->monthly();

        $schedule->command('tc:payment-details:check-expired')->runInBackground()->daily();
        $schedule->command('tc:payment-details:check-archive')->runInBackground()->daily();
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

<?php

namespace App\Console;

use App\Console\Commands\CheckArchivePaymentDetails;
use App\Console\Commands\CheckExpiredPaymentDetails;
use App\Console\Commands\CheckHeroMembershipExpiry;
use App\Console\Commands\DumpNotifications;
use App\Console\Commands\Removing\RemoveUnverifiedUsers;
use App\Console\Commands\UpdateCryptoCurrenciesPrices;
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
        UpdateCryptoCurrenciesPrices::class,
        DumpNotifications::class,
        CheckExpiredPaymentDetails::class,
        CheckArchivePaymentDetails::class,
        RemoveUnverifiedUsers::class,
        CheckHeroMembershipExpiry::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command("crypto_currencies:update --updateOnlyFirst250=true")->runInBackground()->everyMinute();
        $schedule->command("crypto_currencies:update --updateOnlyFirst250=false")->runInBackground()->everyTenMinutes();

        $schedule->command('tc:channels:check-monetization-qualification')->runInBackground()->daily();

        $keep = config('general.notifications.keep');
        $schedule->command("notifications:dump --keep={$keep}")->runInBackground()->monthly();

        $schedule->command('tc:payment-details:check-expired')->runInBackground()->daily();
        $schedule->command('tc:payment-details:check-archive')->runInBackground()->daily();

        $schedule->command('tc:auth-keys:remove-old')->runInBackground()->everyFourHours();

        $schedule->command('tc:users:remove-unverified')->runInBackground()->daily();

        $schedule->command('telescope:prune')->daily();

        $schedule->command('plans:check-hero-expiry')->daily();

        $schedule->command('tc:security:check-suspicious-ip')->runInBackground()->everyTenMinutes();

        $schedule->command('tcg:update-claimable')->runInBackground()->dailyAt('08:00');

        $schedule->command('tc:monetization:calc')->runInBackground()->dailyAt('02:00');
        $schedule->command('tc:monetization:send-mail')->runInBackground()->monthlyOn(2, '01:00');
        //$schedule->command('tc:monetization:add-subscribers-points')->runInBackground()->monthlyOn(1, '01:00'); No longer add subscription point to monetization point

        $schedule->command('auto_import')->runInBackground()->everyMinute();

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

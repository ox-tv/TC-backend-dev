<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\DbDumper\Databases\MySql;
use Storage;

class DumpNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:dump {--period= : 1W,1M,3M,6M}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump notifications table and pivot table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $validator = Validator::make([
            'period' => $this->option('period'),
        ], [
            'period' => ['required', Rule::in(['1W', '1M', '3M', '6M'])],
        ]);

        if ($validator->fails()) {
            $this->alert('Some options is not valid:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            $this->info('Type "php artisan notifications:dump -h" to help');
            return 1;
        }

        $period = $this->option('period');
        $is_time = $this->{"check".$period}();

        dd($is_time);

        $dbInfo = config('database.connections.'.config('database.default'));
        $path = Storage::disk('dumps')->path('notifications-' . date('Y-m-d-H-i-s')) . '.sql';

        MySql::create()
            ->setDbName($dbInfo['database'])
            ->setUserName($dbInfo['username'])
            ->setPassword($dbInfo['password'])
            ->includeTables(['notifications', 'notification_user'])
            ->dumpToFile($path);
    }

    private function check1W()
    {
        $now = Carbon::now();
        return $now->endOfWeek()->format('Y-m-d H:i');
        return $now->startOfWeek()->format('Y-m-d H:i');
    }

    private function check1M()
    {
        return true;
    }

    private function check3M()
    {
        return true;
    }

    private function check6M()
    {
        return true;
    }
}

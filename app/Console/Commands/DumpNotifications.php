<?php

namespace App\Console\Commands;

use App\Models\Notification;
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
    protected $signature = 'notifications:dump {--keep= : Number of days to keep}';

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
            'keep' => $this->option('keep'),
        ], [
            'keep' => ['required', 'numeric', 'gte:1', 'lte:365'],
        ]);

        if ($validator->fails()) {
            $this->alert('Some options is not valid:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            $this->info('Type "php artisan notifications:dump -h" to help');
            return 1;
        }

        $keep = $this->option('keep');
        $dbInfo = config('database.connections.'.config('database.default'));
        $path = Storage::disk('dumps')->path('notifications-' . date('Y-m-d-H-i-s')) . '.sql';

        MySql::create()
            ->setDbName($dbInfo['database'])
            ->setUserName($dbInfo['username'])
            ->setPassword($dbInfo['password'])
            ->includeTables(['notifications', 'notification_user'])
            ->dumpToFile($path);

        Notification::where('created_at', '<', Carbon::now()->subDays($keep))
            ->whereExists(function ($query) {
                $query->from('notification_user')
                    ->whereColumn('notification_user.notification_id', 'notifications.id')
                    ->whereNotNull('read_at');
            })->delete();

        return 0;
    }
}

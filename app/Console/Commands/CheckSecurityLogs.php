<?php

namespace App\Console\Commands;

use App\Models\SecurityRateLimit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class CheckSecurityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check rate limit logs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $logFileHandle = fopen(storage_path().'/logs/ratelimit/api-2023-11-18.log', 'r');

        if ($logFileHandle) {
            while (($line = fgets($logFileHandle)) !== false) {
                $dataPart = explode("production.ERROR:", $line)[1];
                $data = explode(" / ", $dataPart);

                $ip = trim($data[0], " IP:");
                $date = trim($data[1], " date: ");
                $userId = trim($data[2], "UserID: ");
                $route = trim($data[3], "\n");

                $dateTime = Carbon::createFromFormat('Y-m-d H:i:s',  $date);

                $securityRateLimit = new SecurityRateLimit();
                $securityRateLimit->timestamps = false;

                $securityRateLimit->ip_address = $ip;
                $securityRateLimit->user_id = $userId;
                $securityRateLimit->route = $route;
                $securityRateLimit->created_at = $dateTime;

                $securityRateLimit->save();

            }

            fclose($logFileHandle);
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands\Security;

use App\Models\PaymentDetails;
use App\Models\SecurityRateLimit;
use App\Models\TokenPoint;
use App\Models\User;
use App\Models\WAFNotValidRequestLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckSuspiciousIPAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:security:check-suspicious-ip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Suspicious IP Address';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ipAddress = [];

        // Get ip By registration_ip field
        $data = User::selectRaw('registration_ip, COUNT(*) as cnt')
            ->where('created_at', '>=', Carbon::parse('2023-11-20 11:12:50'))
            ->where('status', User::STATUS_ACTIVE)
            ->where('registration_ip','!=', 'undefined')
            ->groupBy('registration_ip')
            ->get()->toArray();
        $result = [];
        foreach ($data as $item) {
            $ip = $item['registration_ip'];
            $key = '';
            if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $key = implode(':', array_slice(explode(':', $ip), 0, 3));
            }elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $key = implode('.', array_slice(explode('.', $ip), 0, 3));
            }else{
                dd($item, $data);
            }

            $result[$key] = ($result[$key]??0) + $item['cnt'];
        }

        $ipAddress = array_keys(array_filter($result, function($v){
            return $v >= 5;
        }));


        // Get ip By last_active_from_ip field
        $data = User::selectRaw('last_active_from_ip, COUNT(*) as cnt')
            ->where('last_actived_at', '>=', Carbon::parse('2023-11-25'))
            ->where('status', User::STATUS_ACTIVE)
            ->where('last_active_from_ip','!=', 'undefined')
            ->groupBy('last_active_from_ip')
            ->get()->toArray();
        $result = [];
        foreach ($data as $item) {
            $ip = $item['last_active_from_ip'];
            $key = '';
            if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $key = implode(':', array_slice(explode(':', $ip), 0, 3));
            }elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $key = implode('.', array_slice(explode('.', $ip), 0, 3));
            }else{
                dd($item, $data);
            }

            $result[$key] = ($result[$key]??0) + $item['cnt'];
        }

        $ipAddress = array_merge($ipAddress, array_keys(array_filter($result, function($v){
            return $v >= 5;
        })));


        // Get ip By not valid requests
        $date = Carbon::now()->format('Y-m-d');
        $ipAddress = array_merge($ipAddress, (new WAFNotValidRequestLog())
            ->setCollection("not_valid_request_{$date}")
            ->raw(function($collection){
                return $collection->distinct('ip_address');
            }));


        $ipAddress = array_unique($ipAddress);

        $OldSuspiciousIpAddress = \App\Models\WAFSuspiciousIPAddress::pluck('ip_address')->toArray();

        $ipAddress = array_filter($ipAddress, function($v) use ($OldSuspiciousIpAddress){
            return !in_array($v, $OldSuspiciousIpAddress);
        });

        $dataToInsert = array_values(array_map(function($a) {
            return ['ip_address' => $a];
        }, $ipAddress));

        \App\Models\WAFSuspiciousIPAddress::insert($dataToInsert);

        return 0;
    }
}

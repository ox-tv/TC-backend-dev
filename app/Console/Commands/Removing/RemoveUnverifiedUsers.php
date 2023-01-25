<?php

namespace App\Console\Commands\Removing;

use App\Models\User;
use App\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemoveUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:publishers:set-channel-name-as-referral-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired pasyment details and change status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userIds = User::whereNull('email_verified_at')->where('created_at', '<', Carbon::now()->subDays(30))->withTrashed()->pluck('id')->toArray();

        UserMeta::whereIn('user_id', $userIds)->delete();
        User::whereIn('id', $userIds)->delete();

        return 0;
    }
}

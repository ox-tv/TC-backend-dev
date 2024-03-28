<?php

namespace App\Console\Commands\Token;

use App\Models\TokenPoint;
use App\Models\User;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GiveTokenToReferrals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:token:give-to-referrals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload channels old avatar to s3 and put in database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userIds = User::where('referrer_id', 98)
            ->whereDate('created_at', '>=', Carbon::parse('2024-03-20'))
            ->pluck('id');

        $repository = new TokenPointRepository();

        foreach ($userIds as $userId){
            $repository->add([
                'user_id' => $userId,
                'type' => TokenPoint::TYPE_REFERRAL_VIA_PUBLISHER,
                'amount' => 200,
            ]);
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetUserReferralCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-referral-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all users referral code';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        User::withTrashed()->update(['referral_code' => null]);

        $users = User::withTrashed()->get();

        foreach ($users as $user){
            do{
                $referral_code = strtoupper(Str::random(6));
            }while(User::where('referral_code', $referral_code)->exists());

            $user->referral_code = $referral_code;
            $user->save();
        }

        return 0;
    }
}

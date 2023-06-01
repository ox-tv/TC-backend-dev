<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FillUserReferralCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fill-referral-code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill users referral code if is null';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::withTrashed()->whereNull('referral_code')->get();

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

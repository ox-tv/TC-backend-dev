<?php

namespace App\Console\Commands\ModifingDatabaseData;

use App\Models\PaymentDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetPublishersChannelNameToReferralCode extends Command
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
        $publishers = User::whereHas('channel')->get();

        foreach ($publishers as $publisher){
            $channel = $publisher->channel;
            $publisher->referral_code = Str::slug($channel->name);
            $publisher->save();
        }

        return 0;
    }
}

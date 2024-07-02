<?php

namespace App\Console\Commands\Monetization;

use App\Mail\MagicLoginMail;
use App\Mail\MonetizationMail;
use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\Monetization;
use App\Models\MonetizationPayout;
use App\Models\MonetizePoint;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonetizationMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:monetization:send-mail {--month=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Monetization mails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $tokenPointRepository;

    public function handle()
    {
        $month = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        if ($this->option('month')){
            $month = Carbon::parse($this->option('month'))->startOfMonth()->format('Y-m-d');
        }

        $monetizationMonth = Monetization::whereDate('month', $month)->first();
        if (!$monetizationMonth){
            dump('no budget...');
            return 0;
        }

        $monetizationPayouts = MonetizationPayout::where('monetization_id', $monetizationMonth->id)->get();

        foreach ($monetizationPayouts as $monetizationPayout){

            $channel = $monetizationPayout->channel;
            if ($channel->owner->email){
                Mail::to($channel->owner->email)->queue(new MonetizationMail($channel->name, $monetizationPayout->amount));
            }
        }

        return 0;
    }


}

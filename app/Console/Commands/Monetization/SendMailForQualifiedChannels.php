<?php

namespace App\Console\Commands\Monetization;

use App\Mail\ChannelQualifiedMail;
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

class SendMailForQualifiedChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:monetization:send-channel-qualified-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate qualified channel\'s monetization';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $tokenPointRepository;

    public function handle()
    {
        $qualifiedChannels = Channel::whereNotNull('monetization_qualified_at')
            ->where('monetization_qualified_at', '<=', Carbon::now())
            ->get();

        foreach ($qualifiedChannels as $channel){
            Mail::to($channel->owner->email)->queue(new ChannelQualifiedMail($channel->name));
        }

        return 0;
    }


}

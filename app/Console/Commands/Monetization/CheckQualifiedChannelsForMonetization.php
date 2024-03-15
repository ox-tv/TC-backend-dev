<?php

namespace App\Console\Commands\Monetization;

use App\Libraries\TCPolygonClient;
use App\Mail\ChannelQualifiedMail;
use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Repository\Eloquent\MonetizePointRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckQualifiedChannelsForMonetization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:channels:check-monetization-qualification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Channels monitazation qualification';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $minimumSubscribers = 500;
        $minimumWatchHoursOnChannel = 2000 * 60 * 60;

        $channels = Channel::whereNull('monetization_qualified_at')->get();

        foreach ($channels as $channel){

            $watchTimeTotal = intval(channel2StatisticsDaily::where('channel_id', $channel->id)->sum('watch_time_total'));
            $subscribersTotal = intval(channel2StatisticsDaily::where('channel_id', $channel->id)->sum('subscribers_total')) - intval(channel2StatisticsDaily::where('channel_id', $channel->id)->sum('unsubscribers_total'));

            dump("{$channel->id},{$channel->name},{$watchTimeTotal},{$subscribersTotal}");

            if ($subscribersTotal < $minimumSubscribers){
                continue;
            }

            if ($watchTimeTotal < $minimumWatchHoursOnChannel){
                continue;
            }

            $channel->monetization_qualified_at = Carbon::now();
            $channel->save();

            Mail::to($channel->owner->email)->queue(new ChannelQualifiedMail($channel->name));
        }


        // Check qualified channels token balance and calculate multiplier
        $qualifiedChannels = Channel::whereNotNull('monetization_qualified_at')->get();
        $monetizePointRepository = new MonetizePointRepository();
        $polygonClient = new TCPolygonClient();

        foreach ($qualifiedChannels as $channel){

            $monetizationMultiplier = 1;

            if ($channel->owner->verifiedPaymentDetails){
                $walletAddress = $channel->owner->verifiedPaymentDetails->eth_address;
                $res = $polygonClient->getBalanceByOwner($walletAddress);

                if ($res['success']){
                    $walletBalance = $res['balance'];
                    $monetizationMultiplier = $monetizePointRepository->ConvertBalanceToMonetizationMultiplier($walletBalance);
                }
            }

            $channel->monetization_multiplier = $monetizationMultiplier;
            $channel->save();
        }

        return 0;
    }
}

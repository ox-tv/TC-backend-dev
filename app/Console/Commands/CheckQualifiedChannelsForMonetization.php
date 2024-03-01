<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use Carbon\Carbon;
use Illuminate\Console\Command;

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

        $channels = Channel::whereNull('monetization_qualified_at')->take(10)->get();

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
        }

        return 0;
    }
}

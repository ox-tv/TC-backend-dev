<?php

namespace App\Console\Commands\Monetization;

use App\Mail\ChannelQualifiedMail;
use App\Mail\MagicLoginMail;
use App\Mail\MonetizationMail;
use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\ChannelUser;
use App\Models\Monetization;
use App\Models\MonetizationPayout;
use App\Models\MonetizePoint;
use App\Repository\Eloquent\MonetizePointRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CalcAndAddSubscribersPointsToMonetizePoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:monetization:add-subscribers-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $tokenPointRepository;

    public function handle()
    {
        $repository = new MonetizePointRepository();
        $pointsPerChannelSubscribedAsHero = config('points.monetize.per_subscribe_channel_as_hero');
        $pointsPerChannelSubscribedAsNonHero = config('points.monetize.per_subscribe_channel_as_non_hero');

        $qualifiedChannels = Channel::whereNotNull('monetization_qualified_at')
            ->where('monetization_qualified_at', '<=', Carbon::now())
            ->get();

        foreach ($qualifiedChannels as $channel){
            $total = $channel->subscribers()->wherePivot('created_at', '<', Carbon::now()->startOfMonth())->count();
            $hero = $channel->heroSubscribers()->wherePivot('created_at', '<', Carbon::now()->startOfMonth())->count();
            $nonHero = $total - $hero;

            $point = ($hero * $pointsPerChannelSubscribedAsHero) + ($nonHero * $pointsPerChannelSubscribedAsNonHero);

            $repository->add([
                'channel_id' => $channel->id,
                'activated_at' => Carbon::now(),
                'type' => MonetizePoint::TYPE_SUBSCRIPTION,
                'amount' => $point,
                'monetization_multiplier' => $channel->monetization_multiplier,
                'date' => Carbon::now()->startOfMonth(),
            ], [
                'channel_id',
                'type',
                'date',
            ]);
        }

        return 0;
    }


}

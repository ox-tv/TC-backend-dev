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

class CalculateMonetizationForGivenMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:monetization_calc_4_month';

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
        $now = Carbon::now();
        $startOfMonth = $now->copy()->subDay()->startOfMonth();
        $endOfMonth = $now->copy()->subDay()->endOfMonth();

        $totalMonthPoints = MonetizePoint::active()
            ->where(function ($q) use($startOfMonth, $endOfMonth){
                $q->where(function($q) use($startOfMonth, $endOfMonth){
                    $q
                        ->where('date', '>=', $startOfMonth)
                        ->where('date', '<=', $endOfMonth)
                        ->where('type', '!=', MonetizePoint::TYPE_SUBSCRIPTION);
                })->orWhere(function($q) use($endOfMonth){
                    $q->where('date', '<=', $endOfMonth)
                        ->where('type', MonetizePoint::TYPE_SUBSCRIPTION);
                });
            })->sum('amount');

        $monthRate = $totalMonthPoints > 0 ? 100 / $totalMonthPoints : 0;

        $qualifiedChannels = Channel::whereNotNull('monetization_qualified_at')
            ->where('monetization_qualified_at', '<=', $now)
            ->get();

        dump("channelId,channelName,points,subTotal,watchTimesInHour,views");

        foreach ($qualifiedChannels as $channel){

            // subscribers
            $subTotal = $channel->subscribers_count;
            $subHero = $channel->hero_subscribers_count;
            $subNonHero = $subTotal - $subHero;

            $views = Channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $startOfMonth)
                ->where('date', '<=', $endOfMonth)
                ->sum('views_total');
            $watchTimes = Channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $startOfMonth)
                ->where('date', '<=', $endOfMonth)
                ->sum('watch_time_total');

            // Likes
            $likesTotal = Channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $startOfMonth)
                ->where('date', '<=', $endOfMonth)
                ->sum('likes_total');
            $likesHero = Channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $startOfMonth)
                ->where('date', '<=', $endOfMonth)
                ->sum('likes_hero');
            $likesNoneHero = $likesTotal - $likesHero;

            // Calc Points
            $points = MonetizePoint::active()
                ->where('channel_id', $channel->id)
                ->where(function ($q) use($startOfMonth, $endOfMonth){
                    $q->where(function($q) use($startOfMonth, $endOfMonth){
                        $q
                            ->where('date', '>=', $startOfMonth)
                            ->where('date', '<=', $endOfMonth)
                            ->where('type', '!=', MonetizePoint::TYPE_SUBSCRIPTION);
                    })->orWhere(function($q) use($endOfMonth){
                        $q->where('date', '<=', $endOfMonth)
                            ->where('type', MonetizePoint::TYPE_SUBSCRIPTION);
                    });
                })->sum('amount');

/*            $monetizationPayout->metrics = [
                'subscribers_total' => $subTotal,
                'subscribers_hero' => $subHero,
                'subscribers_non_hero' => $subNonHero,
                'views' => $views,
                'watch_times' => $watchTimes,
                'likes_total' => $likesTotal,
                'likes_hero' => $likesHero,
                'likes_non_hero' => $likesNoneHero,
                'points' => $points,
                'share' => $totalMonthPoints > 0 ? $points / $totalMonthPoints * 100 : 0,
            ];*/

            $watchTimesInHour = $watchTimes / (60*60);

            dump("{$channel->id},{$channel->name},{$points},{$subTotal},{$watchTimesInHour},{$views}");

        }

        return 0;
    }


}

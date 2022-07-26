<?php


namespace App\Services;


use App\Models\User;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class PointService
{
    public function calcPoint(User $user, $filters = [])
    {
        $points = 0;
        $channel = $user->channel;

        $from = Arr::get($filters, 'from');
        $to = Arr::get($filters, 'to');

        // Calc views, likes and dislikes points
        $q = VideoStatisticsDaily::where('channel_id', $channel->id);

        if(!empty($from)){
            $q->where('date', '>=', Carbon::parse($from));
        }

        if(!empty($to)){
            $q->where('date', '<=', Carbon::parse($to));
        }

        $points += $q->sum('points');

        // Calc channel subscribers points
        $pointPerHeroSub = config('general.points.per_subscribe_hero');
        $pointPerNonHeroSub = config('general.points.per_subscribe_non_hero');

        $heroSubCounts = $channel->subscribers()->when($to, function ($q, $to){
            $q->where('channel_user.created_at', '<=', $to);
        })->isHero()->count();
        $nonHeroSubCounts = $channel->subscribers()->when($to, function ($q, $to){
            $q->where('channel_user.created_at', '<=', $to);
        })->isNonHero()->count();

        $points += ($heroSubCounts * $pointPerHeroSub);
        $points += ($nonHeroSubCounts * $pointPerNonHeroSub);

        return $points;
    }

    public function calcHeroPoint(User $user, $filters = [])
    {
        $points = 0;
        $channel = $user->channel;

        $from = Arr::get($filters, 'from');
        $to = Arr::get($filters, 'to');

        // Calc views, likes and dislikes points
        $q = VideoStatisticsDaily::where('channel_id', $channel->id);

        if(!empty($from)){
            $q->where('date', '>=', Carbon::parse($from));
        }

        if(!empty($to)){
            $q->where('date', '<=', Carbon::parse($to));
        }

        $points += $q->sum('point_details->hero');

        // Calc channel hero subscribers points
        $pointPerHeroSub = config('general.points.per_subscribe_hero');
        $heroSubCounts = $channel->subscribers()->when($to, function ($q, $to){
            $q->where('channel_user.created_at', '<=', $to);
        })->isHero()->count();
        $points += ($heroSubCounts * $pointPerHeroSub);

        return $points;
    }

    public function calcNonHeroPoint(User $user, $filters = [])
    {
        $points = 0;
        $channel = $user->channel;

        $from = Arr::get($filters, 'from');
        $to = Arr::get($filters, 'to');

        // Calc views, likes and dislikes points
        $q = VideoStatisticsDaily::where('channel_id', $channel->id);

        if(!empty($from)){
            $q->where('date', '>=', Carbon::parse($from));
        }

        if(!empty($to)){
            $q->where('date', '<=', Carbon::parse($to));
        }

        $points += $q->sum('point_details->non_hero');

        // Calc channel non hero subscribers points
        $pointPerNonHeroSub = config('general.points.per_subscribe_non_hero');
        $nonHeroSubCounts = $channel->subscribers()->when($to, function ($q, $to){
            $q->where('channel_user.created_at', '<=', $to);
        })->isNonHero()->count();
        $points += ($nonHeroSubCounts * $pointPerNonHeroSub);

        return $points;
    }
}
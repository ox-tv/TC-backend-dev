<?php

namespace App\Http\Controllers;

use App\Models\ChannelStatisticsDaily;
use App\Models\MonetizePoint;
use App\Models\UserMeta;
use App\Models\VideoStatisticsDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ReferralController extends Controller
{
    public function setPointsToActive()
    {
        $user = auth('api')->user();
        $channel = $user->channel;

        if(!$channel){
            abort(404, 'channel not found.');
        }

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::MonetizeReferralPointsIsActive],
            ['value' => true]
        );

        MonetizePoint::where('type', MonetizePoint::TYPE_REFERRAL)
            ->where('channel_id', $channel->id)
            ->update(['activated_at' => MonetizePoint::fromDateTime(Carbon::now())]);

        return response()->json(['status' => 'ok']);
    }

    public function statistics(Request $request)
    {
        $result = [
            'overview' => [
                'total_referrals' => 0,
                'total_referral_points' => 0,
                'this_month_referrals' => 0,
                'this_month_referral_points' => 0,
            ],
            'referral_code' => auth('api')->user()->referral_code,
            'statistics' => [],
        ];

        $channel = auth('api')->user()->channel;

        if(!$channel){
            abort(404, 'channel not found.');
        }

        // Overview
        $result['overview']['total_referrals'] = ChannelStatisticsDaily::raw(function($collection) use ($channel){
            return $collection->aggregate([
                ['$match' => [
                    'channel_id' => $channel->id,
                ]],
                ['$group' => [
                    '_id' => null,
                    'amount' => ['$sum' => ['$subtract' => ['$subscribers_total', '$unsubscribers_total']]],
                ]],
            ]);
        })->pluck('amount')->first()?? 0;

        $result['overview']['total_referral_points'] = MonetizePoint::where('channel_id', $channel->id)->where('type', MonetizePoint::TYPE_REFERRAL)->sum('amount');

        $result['overview']['this_month_referrals'] = ChannelStatisticsDaily::raw(function($collection) use ($channel){
                return $collection->aggregate([
                    ['$match' => [
                        'channel_id' => $channel->id,
                        'date' => ['$gte'=> ChannelStatisticsDaily::fromDateTime(Carbon::now()->startOfMonth())],
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'amount' => ['$sum' => ['$subtract' => ['$subscribers_total', '$unsubscribers_total']]],
                    ]],
                ]);
            })->pluck('amount')->first()?? 0;

        $result['overview']['this_month_referral_points'] = MonetizePoint::where('channel_id', $channel->id)
            ->where('type', MonetizePoint::TYPE_REFERRAL)
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        // Statistics
        $filters = $request->get('filters', []);
        $period = Arr::get($filters, 'statistics_period', 'last_30d');

        switch ($period) {
            case 'last_7d';
                $from = Carbon::now()->subDays(7)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_14d';
                $from = Carbon::now()->subDays(14)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_90d';
                $from = Carbon::now()->subDays(90)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_180d';
                $from = Carbon::now()->subDays(180)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_365d';
                $from = Carbon::now()->subDays(365)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
            case 'last_30d';
            default;
                $from = Carbon::now()->subDays(30)->startOfDay();
                $to = Carbon::now()->endOfDay();
                break;
        }

        $result['statistics'] = in_array($period, ['this_year', 'last_365d', 'last_180d'])? $this->monthlyStatistics($channel, $from, $to) : $this->dailyStatistics($channel, $from, $to);

        return $result;
    }

    private function dailyStatistics($channel, $from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);

        foreach ($periods as $day) {
            $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')))->get();

            $monetizePointsQuery = MonetizePoint::where('channel_id', $channel->id)
                ->where('type', MonetizePoint::TYPE_REFERRAL)
                ->where('date', Carbon::parse($day->format('Y-m-d')));

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'points' => natural_intval($monetizePointsQuery->sum('amount')),
                'subscribers_total' => natural_intval($channelStatisticsQuery->sum('subscribers_total')),
                'unsubscribers_total' => natural_intval($channelStatisticsQuery->sum('unsubscribers_total')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($channel, $from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $channelStatisticsQuery = channelStatisticsDaily::where('channel_id', $channel->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $monetizePointQuery = MonetizePoint::where('channel_id', $channel->id)
                ->where('type', MonetizePoint::TYPE_REFERRAL)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $statistics[$date] = [
                'date' => $date,
                'points' => intval($monetizePointQuery->sum('amount')),
                'subscribers_total' => ($temp = $channelStatisticsQuery->sum('subscribers_total')) > 0? intval($temp) : 0,
                'unsubscribers_total' => ($temp = $channelStatisticsQuery->sum('unsubscribers_total')) > 0? intval($temp) : 0,
            ];
        }

        return $statistics;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MonetizePoint;
use App\Models\UserMeta;
use App\Models\UserStatisticsDaily;
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
        $user = auth('api')->user();
        $channel = $user->channel;

        $result = [
            'overview' => [
                'total_referrals' => 0,
                'total_referral_points' => 0,
                'this_month_referrals' => 0,
                'this_month_referral_points' => 0,
            ],
            'referral_code' => $user->referral_code,
            'monetize_referral_points_is_active' => ($meta = $user->meta()->where('key', UserMeta::MonetizeReferralPointsIsActive)->first()) && (bool)$meta->value,
            'statistics' => [],
        ];

        if(!$channel){
            abort(404, 'channel not found.');
        }

        // Overview
        $result['overview']['total_referrals'] = UserStatisticsDaily::where('user_id', $user->id)->sum('referral_count_total');
        $result['overview']['total_referral_points'] = MonetizePoint::where('channel_id', $channel->id)->where('type', MonetizePoint::TYPE_REFERRAL)->sum('amount');

        $result['overview']['this_month_referrals'] = UserStatisticsDaily::where('user_id', $user->id)
            ->where('date', '>=', Carbon::now()->startOfMonth())
            ->sum('referral_count_total');

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

        $result['statistics'] = in_array($period, ['this_year', 'last_365d', 'last_180d'])? $this->monthlyStatistics($user, $from, $to) : $this->dailyStatistics($user, $from, $to);

        return $result;
    }

    private function dailyStatistics($user, $from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);
        $channel = $user->channel;

        foreach ($periods as $day) {
            $userStatisticsQuery = UserStatisticsDaily::where('user_id', $user->id)
                ->where('date', Carbon::parse($day->format('Y-m-d')));

            $monetizePointsQuery = MonetizePoint::where('channel_id', $channel->id)
                ->where('type', MonetizePoint::TYPE_REFERRAL)
                ->where('date', Carbon::parse($day->format('Y-m-d')));

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'points' => natural_intval($monetizePointsQuery->sum('amount')),
                'referral_count_total' => natural_intval($userStatisticsQuery->sum('referral_count_total')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($user, $from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);
        $channel = $user->channel;

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $userStatisticsQuery = UserStatisticsDaily::where('user_id', $user->id)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $monetizePointQuery = MonetizePoint::where('channel_id', $channel->id)
                ->where('type', MonetizePoint::TYPE_REFERRAL)
                ->where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())->get();

            $statistics[$date] = [
                'date' => $date,
                'points' => intval($monetizePointQuery->sum('amount')),
                'referral_count_total' => natural_intval($userStatisticsQuery->sum('referral_count_total')),
            ];
        }

        return $statistics;
    }
}

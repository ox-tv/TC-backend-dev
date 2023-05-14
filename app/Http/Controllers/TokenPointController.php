<?php

namespace App\Http\Controllers;

use App\Http\Resources\TokenPoint\TokenPointResource;
use App\Models\TokenPoint;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TokenPointController extends Controller
{
    public function index(Request $request)
    {
        $adminRoute = $request->is('api/admin/*');

        $filters = $request->get('filters', []);
        $userIdFilter = Arr::get($filters, 'user_id');

        if (!$adminRoute){
            $userIdFilter = auth('api')->id();
        }

        $query = TokenPoint::query();

        if ($userIdFilter){
            $query->where('user_id', intval($userIdFilter));
        }

        return TokenPointResource::collection($query->paginate());
    }

    public function overview()
    {
        $result = [
            'total_tokens' => 3500000000,
            'total_tokens_distributed' => 0,
            'today_tokens_distributed' => 0,
            'user_total_tokens' => null,
            'user_locked_tokens' => null,
        ];

        $result['total_tokens_distributed'] = TokenPoint::sum('amount');
        $result['today_tokens_distributed'] = TokenPoint::where('date', Carbon::now()->startOfDay())->sum('amount');

        if (auth('api')->check()){
            $result['user_total_tokens'] = TokenPoint::where('user_id', auth('api')->id())->where('activate_at', '<=', Carbon::now())->sum('amount');
            $result['user_locked_tokens'] = TokenPoint::where('user_id', auth('api')->id())->where('activate_at', '<=', Carbon::now())->whereNull('claimable_at')->sum('amount');
        }

        return response()->json($result);
    }

    public function adminDashboard(Request $request)
    {
        $result = [
            'overview' => [
                'total_tokens' => 3500000000,
                'total_tokens_distributed' => TokenPoint::sum('amount'),
            ],
            'statistics' => [],
        ];

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

        $result['statistics'] = in_array($period, ['this_year', 'last_365d', 'last_180d'])? $this->monthlyStatistics($from, $to) : $this->dailyStatistics($from, $to);

        return response()->json($result);
    }

    private function dailyStatistics($from, $to): array
    {
        $statistics = [];
        $periods = CarbonPeriod::create($from, '1 day', $to);

        foreach ($periods as $day) {
            $heroQuery = TokenPoint::where('date', Carbon::parse($day->format('Y-m-d')))
                ->whereIn('type', TokenPoint::TYPE_FOR_HERO);
            $userQuery = TokenPoint::where('date', Carbon::parse($day->format('Y-m-d')))
                ->whereIn('type', TokenPoint::TYPE_FOR_USER);
            $publisherQuery = TokenPoint::where('date', Carbon::parse($day->format('Y-m-d')))
                ->whereIn('type', TokenPoint::TYPE_FOR_PUBLISHER);

            $statistics[$day->format('Y-m-d')] = [
                'date' => $day->format('Y-m-d'),
                'hero' => intval($heroQuery->sum('amount')),
                'user' => intval($userQuery->sum('amount')),
                'publisher' => intval($publisherQuery->sum('amount')),
            ];
        }

        return $statistics;
    }

    private function monthlyStatistics($from, $to): array
    {
        $statistics = [];
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);

        foreach ($monthPeriods as $month) {
            $date = $month->copy()->startOfMonth()->format("Y-m-d");

            $heroQuery = TokenPoint::where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())
                ->whereIn('type', TokenPoint::TYPE_FOR_HERO);
            $userQuery = TokenPoint::where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())
                ->whereIn('type', TokenPoint::TYPE_FOR_USER);
            $publisherQuery = TokenPoint::where('date', '>=', $month->copy()->startOfMonth())
                ->where('date', '<=', $month->copy()->endOfMonth())
                ->whereIn('type', TokenPoint::TYPE_FOR_PUBLISHER);

            $statistics[$date] = [
                'date' => $date,
                'hero' => intval($heroQuery->sum('amount')),
                'user' => intval($userQuery->sum('amount')),
                'publisher' => intval($publisherQuery->sum('amount')),
            ];
        }

        return $statistics;
    }
}

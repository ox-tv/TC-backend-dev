<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Pricing;
use App\Models\PricingUser;
use App\Models\User;
use App\Repository\PricingRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HeroMembershipController extends Controller
{
    private $pricingRepository;

    public function __construct(PricingRepositoryInterface $pricingRepository)
    {
        $this->pricingRepository = $pricingRepository;
    }


    public function store(Request $request, Pricing $pricing)
    {
        $exists = $pricing->plan()->where('status', Plan::STATUS_ACTIVE)->exists();

        abort_unless($exists, 404);

        if($request->is('api/admin/*')){
            $user = User::findOrFail($request->get('user_id'));
        }else{
            $user = auth('api')->user();
        }

        $this->pricingRepository->addPricingToUser($user, $pricing);

        return response()->json(['message' => 'ok']);
    }

    public function reportTotal(Request $request, $userId = null)
    {
        $filters = $request->get('filters', []);
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');

        $pricingUsers = PricingUser::when($userId, function ($query, $userId) {

            return $query->where('user_id', $userId);

        })->when($fromFilter, function ($query, $fromFilter) {

            return $query->where('created_at', '>=', $fromFilter);

        })->when($toFilter, function ($query, $toFilter) {

            return $query->where('created_at', '<=', $toFilter);
        });

        return [
            'amount' => $pricingUsers->sum('metadata->pricing->amount'),
            'currency' => 'USD',
        ];
    }

    public function ReportMonthly(Request $request, $userId = null)
    {
        $result = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subMonths(12)->firstOfMonth());
        $to = Arr::get($filters, 'to', (Carbon::now())->firstOfMonth());
        $monthPeriods = CarbonPeriod::create($from, '1 month', $to);


        foreach ($monthPeriods as $month) {
            $from_day = $month->startOfMonth()->format("Y-m-d H:i:s");
            $to_day = $month->endOfMonth()->format("Y-m-d H:i:s");

            $pricingUsers = PricingUser::when($userId, function ($query, $userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereDate('created_at', '>=', $from_day)
                ->whereDate('created_at', '<=', $to_day);

            $result[$month->format("Y-m")] = [
                'amount' => $pricingUsers->sum('metadata->pricing->amount'),
                'currency' => 'USD',
            ];
        }

        return $result;
    }

    public function reportDaily(Request $request, $userId = null)
    {
        $statistics = [];

        $filters = $request->get('filters', []);
        $from = Arr::get($filters, 'from', (Carbon::now())->subDays(30)->format('Y-m-d'));
        $to = Arr::get($filters, 'to', (Carbon::now())->format('Y-m-d H:i:s'));


        $periods = CarbonPeriod::create($from, '1 day', $to);
        abort_unless(count($periods) <= 31, 400, 'timespan between from and to is more than 1 month');

        foreach ($periods as $day) {

            $pricingUsers = PricingUser::when($userId, function ($query, $userId) {
                    return $query->where('user_id', $userId);
                })
                ->whereDate('created_at', '>=', $day->format('Y-m-d 00:00:00'))
                ->whereDate('created_at', '<=', $day->format('Y-m-d 23:59:59'));

            $statistics[$day->format('Y-m-d')] = [
                'amount' => $pricingUsers->sum('metadata->pricing->amount'),
                'currency' => 'USD',
            ];
        }

        return $statistics;
    }
}

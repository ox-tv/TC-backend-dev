<?php

namespace App\Console\Commands\Recalculation;

use App\Models\PricingUser;
use App\Models\TokenPoint;
use App\Models\User;
use App\Repository\Eloquent\LoyaltyPointRepository;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecalculateTokenPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:recalc:token-points {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate token points';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $tokenPointRepository;

    public function handle()
    {
        //$date = $this->option('date');
        //$this->recalculateTokensForWatchTimesByDate($date);

        $this->recalculateTokensForBuyingMembership();
        $this->recalculateTokensForFillCustomFeed();

        return 0;
    }

    private function recalculateTokensForFillCustomFeed(): void
    {
        $coinRows = DB::table('crypto_currency_user')
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->get()->toArray();


        $dataToDB = [];
        foreach ($coinRows as $row){
            if ($row->cnt < 3){
                continue;
            }

            $tagCount = DB::table('tag_user')
                ->where('user_id', $row->user_id)
                ->count();

            if ($tagCount < 3){
                continue;
            }

            if (TokenPoint::where('user_id', $row->user_id)->whereIn('type', [TokenPoint::TYPE_CUSTOM_FEED_FIILED, TokenPoint::TYPE_CUSTOM_FEED_FIILED_AS_HERO])->exists()){
                continue;
            }

            $user = User::find($row->user_id);
            if (!$user){continue;}

            $day = $user->created_at;

            $wasHero = $user->hero_due_at >= $day;
            $valuePerToken = $wasHero? config('points.token.fill_custom_feed_as_hero') : config('points.token.fill_custom_feed');
            $tokenType = $wasHero? TokenPoint::TYPE_CUSTOM_FEED_FIILED_AS_HERO : TokenPoint::TYPE_CUSTOM_FEED_FIILED;

            $dataToDB[] = [
                'user_id' => $user->id,
                'type' => $tokenType,
                'amount' => $valuePerToken,
                'date' => TokenPoint::fromDateTime((clone $day)->startOfDay()),
                'activate_at' => TokenPoint::fromDateTime((clone $day)->endOfDay()),
                'claimable_at' => TokenPoint::fromDateTime((clone $day)->addDay()->startOfDay()),
                'claimable_by' => 'FakeByReCalculate',
            ];

        }

        if (!empty($dataToDB)){
            TokenPoint::insert($dataToDB);
        }
    }

    private function recalculateTokensForBuyingMembership(): void
    {
        $pricingUsers = PricingUser::where('status', PricingUser::STATUS_COMPLETED)
            ->where('metadata->plan->interval', 365)
            ->get();

        $rows = [];
        foreach ($pricingUsers as $pricingUser){
            $number = $rows["{$pricingUser->user_id}_{$pricingUser->created_at->format('Y-m-d')}"]??0;
            $number++;
            $rows["{$pricingUser->user_id}_{$pricingUser->created_at->format('Y-m-d')}"] = $number;
        }

        $dataToDB = [];
        foreach ($rows as $key => $number){
            [$userId, $date] = explode('_', $key);
            $day = Carbon::parse($date);

            $user = User::find($userId);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;
            $valuePerToken = $wasHero? config('points.token.buying_yearly_membership_as_hero') : config('points.token.buying_yearly_membership');
            $tokenType = $wasHero? TokenPoint::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP_AS_HERO : TokenPoint::TYPE_BUYING_YEARLY_HERO_MEMBERSHIP;

            $dataToDB[] = [
                'user_id' => $user->id,
                'type' => $tokenType,
                'amount' => $valuePerToken * $number,
                'date' => TokenPoint::fromDateTime((clone $day)->startOfDay()),
                'activate_at' => TokenPoint::fromDateTime((clone $day)->endOfDay()),
                'claimable_at' => TokenPoint::fromDateTime((clone $day)->addDay()->startOfDay()),
                'claimable_by' => 'FakeByReCalculate',
            ];
        }

        if (!empty($dataToDB)){
            TokenPoint::insert($dataToDB);
        }
    }

    /*
     * $date format: Y-m-d
     */
    private function recalculateTokensForWatchTimesByDate($date): void
    {
        $this->tokenPointRepository = new TokenPointRepository();
        $day = Carbon::parse($date);

        $watchTimes = DB::table('watch_times')
            ->whereDate('created_at', $day)
            ->select(["end_time", "start_time", "user_id"])
            ->get()->toArray();

        $durations = [];
        foreach ($watchTimes as $watchtime){
            $duration = $durations[$watchtime->user_id] ?? 0;
            $duration += ($watchtime->end_time - $watchtime->start_time);
            $durations[$watchtime->user_id] = $duration;
        }

        // handle for each user
        $dataToDB = [];
        foreach ($durations as $userId => $duration){

            $user = User::find($userId);
            if (!$user){continue;}

            $wasHero = $user->hero_due_at >= $day;
            $heroMultiplier = $wasHero? 2 : 1;
            $maxTokenToEarn = $day >= Carbon::parse('2023-11-15')? ($wasHero? 180 : 30) : 10000;
            $tokenType = $wasHero? TokenPoint::TYPE_WATCH_A_VIDEO_AS_HERO : TokenPoint::TYPE_WATCH_A_VIDEO;

            $durationInMinute = intval($duration / 60);
            $tokenValue = $durationInMinute * $heroMultiplier;

            $tokenValue = min($tokenValue, $maxTokenToEarn);

            $dataToDB[] = [
                'user_id' => $user->id,
                'type' => $tokenType,
                'amount' => $tokenValue,
                'date' => TokenPoint::fromDateTime((clone $day)->startOfDay()),
                'activate_at' => TokenPoint::fromDateTime((clone $day)->endOfDay()),
                'claimable_at' => TokenPoint::fromDateTime((clone $day)->addDay()->startOfDay()),
                'claimable_by' => 'FakeByReCalculate',
            ];
        }

        if (!empty($dataToDB)){
            TokenPoint::insert($dataToDB);
        }
    }

    private function recalculateYesterdayExceptClaimableTokens2()
    {
        $this->tokenPointRepository = new TokenPointRepository();

        $users = User::where('status', 2)->where('watch_time','>',0)->where('last_actived_at','>',Carbon\Carbon::now()->subMonth())->get();

        foreach ($users as $user){
            $dataToDB = [];
            $periods = CarbonPeriod::create(Carbon::parse('2023-11-01'), '1 day', Carbon::now());

            foreach ($periods as $day) {
                $wasHero = $user->hero_due_at >= $day;
                $heroMultiplier = $wasHero? 2 : 1;
                $maxTokenToEarn = $wasHero? 360 : 30;
                $tokenType = $wasHero? TokenPoint::TYPE_WATCH_A_VIDEO_AS_HERO : TokenPoint::TYPE_WATCH_A_VIDEO;

                $watchTimes = DB::table('watch_times')
                    ->whereDate('created_at', $day)
                    ->where('user_id', $user->id)
                    ->select(["end_time", "start_time"])->get();

                $totalTimes = [];
                foreach ($watchTimes as $watchTime){
                    $totalTimes[] = $watchTime->end_time - $watchTime->start_time;
                }

                $watchTimeDuration = array_sum($totalTimes);

                $durationInMinute = intval($watchTimeDuration / 60);
                $tokenValue = $durationInMinute * $heroMultiplier;

                $tokenValue = min($tokenValue, $maxTokenToEarn);

                $dataToDB[] = [
                    'user_id' => $user->id,
                    'type' => $tokenType,
                    'amount' => $tokenValue,
                    'date' => $day->startOfDay(),
                    'activate_at' => $day->endOfDay(),
                    'claimable_at' => $day->addDay()->startOfDay(),
                    'claimable_by' => 'FakeByReCalculate',
                ];
            }

            // Bulk insert to DB
            TokenPoint::insert($dataToDB);

            // Mark User as recalculated

        }

    }

    private function recalculateYesterdayAndToday()
    {
        $this->tokenPointRepository = new TokenPointRepository();

        // Recalc yesterday
        $watchTimes = DB::table('watch_times')
            ->whereDate('created_at', Carbon::today()->subDays(1))
            ->groupBy('user_id')
            ->selectRaw("SUM(end_time - start_time) as duration, user_id")
            ->get();

        foreach ($watchTimes as $watchTime) {
            $user = User::find($watchTime->user_id);

            $pointType = $user->is_hero? TokenPoint::TYPE_WATCH_A_VIDEO_AS_HERO : TokenPoint::TYPE_WATCH_A_VIDEO;
            $durationInMinute = intval($watchTime->duration / 60);
            $amount = $user->is_hero? $durationInMinute * 2 : $durationInMinute;

            $row = TokenPoint::where('date', Carbon::now()->subDays(1)->startOfDay())
                ->where('user_id', $user->id)
                ->where('type', $pointType)
                ->first();

            if ($row){
                $row->amount = $amount;
                $row->save();
            }else{
                $row = $this->tokenPointRepository->add([
                    'user_id' => $user->id,
                    'type' => $pointType,
                    'amount' => $amount,
                    'date' => Carbon::now()->subDays(1)->startOfDay(),
                    'activate_at' => Carbon::now()->subDays(1),
                ]);
            }
        }

        // Recalc today
        $watchTimes = DB::table('watch_times')
            ->whereDate('created_at', Carbon::today())
            ->groupBy('user_id')
            ->selectRaw("SUM(end_time - start_time) as duration, user_id")
            ->get();

        foreach ($watchTimes as $watchTime) {
            $user = User::find($watchTime->user_id);

            $pointType = $user->is_hero? TokenPoint::TYPE_WATCH_A_VIDEO_AS_HERO : TokenPoint::TYPE_WATCH_A_VIDEO;
            $durationInMinute = intval($watchTime->duration / 60);
            $amount = $user->is_hero? $durationInMinute * 2 : $durationInMinute;

            $row = TokenPoint::where('date', Carbon::now()->startOfDay())
                ->where('user_id', $user->id)
                ->where('type', $pointType)
                ->first();

            if ($row){
                $row->amount = $amount;
                $row->save();
            }else{
                $row = $this->tokenPointRepository->add([
                    'user_id' => $user->id,
                    'type' => $pointType,
                    'amount' => $amount,
                    'date' => Carbon::now()->startOfDay(),
                    'activate_at' => Carbon::now(),
                ]);
            }
        }
    }
}

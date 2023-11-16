<?php

namespace App\Console\Commands\Recalculation;

use App\Models\TokenPoint;
use App\Models\User;
use App\Repository\Eloquent\LoyaltyPointRepository;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;
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
    protected $signature = 'tc:recalc:token-points';

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
        $this->recalculateYesterdayExceptClaimableTokens();

        return 0;
    }

    private function recalculateYesterdayExceptClaimableTokens()
    {
        $this->tokenPointRepository = new TokenPointRepository();

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

            $yesterdayRow = TokenPoint::where('date', Carbon::now()->subDays(1)->startOfDay())
                ->where('user_id', $user->id)
                ->where('type', $pointType)
                ->first();

            if ($yesterdayRow->amount < $amount){
                $todayRow = TokenPoint::where('date', Carbon::now()->startOfDay())
                    ->where('user_id', $user->id)
                    ->where('type', $pointType)
                    ->first();

                if ($todayRow){
                    $todayRow->amount = $todayRow->amount + ($amount - $yesterdayRow->amount);
                    $todayRow->save();
                }else{
                    $todayRow = $this->tokenPointRepository->add([
                        'user_id' => $user->id,
                        'type' => $pointType,
                        'amount' => $amount - $yesterdayRow->amount,
                        //'date' => Carbon::now()->subDays(1)->startOfDay(),
                        //'activate_at' => Carbon::now()->subDays(1),
                    ]);
                }
            }

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

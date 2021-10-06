<?php

namespace App\Http\Controllers;


use App\Http\Resources\Lottery\LotteryItem;
use App\Models\Lottery;
use App\Models\LotteryUser;
use App\Models\Plan;
use App\Models\PricingUser;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotteryController extends Controller
{
    public function index(Request $request)
    {
        $lotteries = Lottery::with(['lottery_users' => function ($query) {
            $query->with('user', 'transaction');
        }])->get();

        return LotteryItem::collection($lotteries);
    }
    public function lottery(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date',
        ]);

        $numberOfUsersToSelect = config('lottery.number_of_users_to_select');

        if ($request->get('month')){
            $month = $request->get('month');
        }else{
            $month = Carbon::now()->subMonthNoOverflow()->format('Y-m-d');
        }

        $firstOfMonth = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
        $firstOfNextMonth = Carbon::parse($month)->endOfMonth()->addDay()->format('Y-m-d');

        // check if lottery is already exists
        if ($lottery = Lottery::where('date',$firstOfMonth)->first()){
            return response()->json([
                'message' => 'Lottery already done for ' . $month,
                'details' => LotteryItem::make($lottery->load(['lottery_users' => function ($query) {
                        $query->with('user', 'transaction');
                    }]))
            ]);
        }

        // Create lottery
        $lottery = new Lottery();
        $lottery->date = $firstOfMonth;

        // Get heroes on selected month
        $plans = Plan::all();
        $users = User::whereHas('pricing', function (Builder $query) use ($firstOfNextMonth, $plans, $firstOfMonth){
            $query->where('pricing_user.status', PricingUser::STATUS_COMPLETED)
                ->whereDate('pricing_user.created_at','<', $firstOfNextMonth);

            $query->where(function (Builder $query) use ($plans, $firstOfMonth) {
                foreach ($plans as $plan){
                    $query->orWhere(function (Builder $query) use ($plan, $firstOfMonth) {
                        return $query->where('pricing_user.metadata->plan->interval', '>=', $plan->interval)
                            ->where('pricing_user.created_at', '>=', Carbon::parse($firstOfMonth)->subdays($plan->interval)->format('Y-m-d'));
                    });
                }
            });

        })->inRandomOrder()->take($numberOfUsersToSelect)->get();

        DB::transaction(function () use ($lottery, $users){
            $lottery->save();

            foreach ($users as $user){
                $lotteryUser = new LotteryUser();
                $lotteryUser->user_id = $user->id;
                $lotteryUser->lottery_id = $lottery->id;
                $lotteryUser->status = LotteryUser::STATUS_PENDING;
                $lotteryUser->save();
            }
        });

        return response()->json([
            'message' => 'Lottery created for ' . $month,
            'lottery' => LotteryItem::make($lottery->load(['lottery_users' => function ($query) {
                    $query->with('user', 'transaction');
                }]))
        ]);
    }

    public function setToPaid(Request $request, $lotteryUserId)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference' => 'required|string',
            'amount' => 'required|gt:0',
        ]);

        $lotteryUser = LotteryUser::whereId($lotteryUserId)
            ->where('status', LotteryUser::STATUS_PENDING)
            ->firstOrFail();

        $lotteryUser->status = LotteryUser::STATUS_PAID;
        $lotteryUser->amount = $request->get('amount');

        $transaction = new Transaction();
        $transaction->amount = $lotteryUser->amount;
        $transaction->type = Transaction::TYPE_WITHDRAW;
        $transaction->status = Transaction::STATUS_COMPLETED;
        $transaction->payment_method_id = $request->get('payment_method_id');
        $transaction->reference = $request->get('reference');
        $transaction->completed_at = date('Y-m-d H:i:s');

        DB::transaction(function () use ($lotteryUser, $transaction){
            $transaction->save();

            $lotteryUser->transaction_id = $transaction->id;
            $lotteryUser->save();
        });

        return response()->json(["status" => "ok"]);
    }
}

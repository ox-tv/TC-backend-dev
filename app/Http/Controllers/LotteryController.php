<?php

namespace App\Http\Controllers;


use App\Models\Lottery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotteryController extends Controller
{
    public function lottery(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date',
        ]);

        if ($request->get('month')){
            $month = Carbon::parse($request->get('month'));
        }else{
            $month = Carbon::now()->subMonth();
        }

        $firstOfMonth = $month->startOfMonth()->format('Y-m-d');
        $endOfMonth = $month->endOfMonth()->format('Y-m-d');

        // check if lottery is already exists
        if ($lottery = Lottery::where('date',$firstOfMonth)->first()){
            return response()->json([
                'message' => 'Lottery already done for ' . $month->format('Y-m'),
                'details' => $lottery->lottery_users
            ]);
        }

        // Create lottery
        $lottery = new Lottery();
        $lottery->date = $firstOfMonth;

        $users =

        DB::transaction(function () use ($lottery){
            $lottery->save();

        });

        return [$month,$firstOfMonth,$endOfMonth];
    }
}

<?php

namespace App\Console\Commands\Monetization;

use App\Mail\ChannelQualifiedMail;
use App\Mail\MagicLoginMail;
use App\Mail\MonetizationMail;
use App\Models\Channel;
use App\Models\Channel2StatisticsDaily;
use App\Models\Monetization;
use App\Models\MonetizationPayout;
use App\Models\MonetizePoint;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckStatisticsAndPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:monetization:check-data {--channel=} {--now=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return int
     */
    private $tokenPointRepository;

    public function handle()
    {
        if (!$this->option('channel')){
            dd("channel=?");
        }

        $channel = Channel::find($this->option('channel'));

        $now = Carbon::now();
        if ($this->option('now')){
            $now = Carbon::parse($this->option('now'));
        }

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $periods = CarbonPeriod::create($startOfMonth, '1 day', $endOfMonth);

        foreach ($periods as $day) {
            $viewsPoint = MonetizePoint::active()
                ->where('channel_id', $channel->id)
                ->where('date', $day)
                ->where('type', MonetizePoint::TYPE_VIDEO_VIEWED)
                ->sum('amount');


            $viewsTotal = Channel2StatisticsDaily::where('channel_id', $channel->id)
                ->where('date', $day)
                ->sum('views_total');

            if (floatval($viewsPoint) != floatval(floatval($viewsTotal)/10)){
                dump($day->format('Y-m-d H:i:s'), $viewsPoint,$viewsTotal);
            }
        }

        return 0;
    }

}

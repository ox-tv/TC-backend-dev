<?php

namespace App\Console\Commands\Recalculation;

use App\Models\LoyaltyPoint;
use App\Models\User;
use App\Models\UserStatisticsDaily;
use App\Repository\Eloquent\LoyaltyPointRepository;
use Illuminate\Console\Command;

class RecalculateLoyaltyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:recalc:loyalty-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ReCalculate loyalty points from UserStatisticsDaily and users table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        LoyaltyPoint::truncate();

        $rows = UserStatisticsDaily::raw(function($collection){
            return $collection->aggregate([
                ['$group' => [
                    '_id' => [
                        "user_id" => '$user_id',
                        "date" => '$date'
                    ],
                    'video_watch_count_as_hero' => ['$sum' => '$video_watch_count_as_hero'],
                    'video_watch_count_as_non_hero' => ['$sum' => '$video_watch_count_as_non_hero'],
                    'comment_liked_count_as_hero' => ['$sum' => '$comment_liked_count_as_hero'],
                    'comment_liked_count_as_non_hero' => ['$sum' => '$comment_liked_count_as_non_hero'],
                    'referral_count_as_hero' => ['$sum' => '$referral_count_as_hero'],
                    'referral_count_as_non_hero' => ['$sum' => '$referral_count_as_non_hero'],
                    'user_id' => ['$first' => '$user_id'],
                    'date' => ['$first' => '$date'],
                ]],
                ['$sort' => ['date' => 1]],
            ]);
        });

        $basePoints = config('points.loyalty');
        $repository = new LoyaltyPointRepository();

        foreach ($rows as $row){
            $commentLikedAmount = ($basePoints['per_comment_liked_as_hero'] * $row->comment_liked_count_as_hero)
                + ($basePoints['per_comment_liked_as_non_hero'] * $row->comment_liked_count_as_non_hero);

            $videoWatchedAmount = ($basePoints['per_watch_video_as_hero'] * $row->video_watch_count_as_hero)
                + ($basePoints['per_watch_video_as_non_hero'] * $row->video_watch_count_as_non_hero);

            $ReferrerAmount = ($basePoints['per_referrer_as_hero'] * $row->referral_count_as_hero)
                + ($basePoints['per_referrer_as_non_hero'] * $row->referral_count_as_non_hero);

            if ($commentLikedAmount > 0){
                $repository->add([
                    'user_id' => $row->user_id,
                    'activated_at' => $row->date,
                    'date' => $row->date,
                    'type' => LoyaltyPoint::TYPE_COMMENT_LIKED,
                    'amount' => $commentLikedAmount,
                ]);
            }

            if ($videoWatchedAmount > 0) {
                $repository->add([
                    'user_id' => $row->user_id,
                    'activated_at' => $row->date,
                    'date' => $row->date,
                    'type' => LoyaltyPoint::TYPE_VIDEO_WATCHED,
                    'amount' => $videoWatchedAmount,
                ]);
            }

            if ($ReferrerAmount > 0) {
                $repository->add([
                    'user_id' => $row->user_id,
                    'activated_at' => $row->date,
                    'date' => $row->date,
                    'type' => LoyaltyPoint::TYPE_REFERRER,
                    'amount' => $ReferrerAmount,
                ]);
            }
        }

        $users = User::whereNotNull('referrer_id')->get();

        foreach ($users as $user){
            $repository->add([
                'user_id' => $user->id,
                'activated_at' => $user->email_verified_at,
                'date' => $user->email_verified_at,
                'type' => LoyaltyPoint::TYPE_REFERRAL,
                'amount' => $basePoints['referral'],
            ]);
        }

        return 0;
    }
}

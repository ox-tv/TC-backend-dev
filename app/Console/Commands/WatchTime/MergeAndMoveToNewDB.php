<?php

namespace App\Console\Commands\WatchTime;

use App\Models\WatchTime;
use App\Models\WatchTimeMongo;
use Illuminate\Console\Command;

class MergeAndMoveToNewDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watch-time:merge-and-move';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge possible records and move to new database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mongoRecord = null;
        WatchTime::where('merge_status', WatchTime::MERGE_STATUS_MERGING)
            ->update(['merge_status'=> null]);

        try {
            while(1){

                $query = WatchTime::where(function ($q){
                    $q->whereNull('merge_status')
                        ->orWhereNotIn('merge_status', [WatchTime::MERGE_STATUS_MERGING, WatchTime::MERGE_STATUS_MERGED]);
                    })->when(!empty($mongoRecord), function ($q) use ($mongoRecord){
                        $q->where('user_id', $mongoRecord->user_id)
                            ->where('video_id', $mongoRecord->video_id)
                            ->where(function($q2) use ($mongoRecord){
                                $q2->where('start_time', $mongoRecord->end_time)
                                    ->orWhere('end_time', $mongoRecord->start_time);
                            });
                    });

                //$query->dump();
                $record = $query->first();

                if (!$record){
                    if (!empty($mongoRecord)){
                        WatchTime::where('merge_status', WatchTime::MERGE_STATUS_MERGING)
                            ->update(['merge_status'=> WatchTime::MERGE_STATUS_MERGED]);

                        $mongoRecord->save();
                        $mongoRecord = null;
                        continue;
                    }
                    else{dump('break!');break;}
                }

                dump($record->id);

                if (empty($mongoRecord)){
                    $mongoRecord = new WatchTimeMongo();
                    $mongoRecord->user_id = $record->user_id;
                    $mongoRecord->video_id = $record->video_id;
                    $mongoRecord->start_time = $record->start_time;
                    $mongoRecord->end_time = $record->end_time;
                    $mongoRecord->created_at = $record->created_at;
                }else{
                    $mongoRecord->start_time = min($mongoRecord->start_time, $record->start_time);
                    $mongoRecord->end_time = max($mongoRecord->end_time, $record->end_time);
                    $mongoRecord->created_at = min($mongoRecord->created_at, $record->created_at);
                }

                $record->merge_status = WatchTime::MERGE_STATUS_MERGING;
                $record->save();
            }
        }catch(\Exception $exception) {
            dump($exception->getCode(), $exception->getMessage());
        }

        return 0;
    }
}

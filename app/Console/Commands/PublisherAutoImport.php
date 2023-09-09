<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PublisherAutoImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto_import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto import publisher contents';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $isChannelImporting = Channel::where('import_request_status', Channel::IMPORT_STATUS_SYNC)->exists();

        if(!$isChannelImporting){

            $channel = Channel::where('status', Channel::STATUS_PUBLISHED)
                ->whereNotNull('youtube_channel_id')
                ->where('youtube_last_scraped_at', '<=', Carbon::now()->subHours(config('yi.auto_import_frequency')))
                ->whereHas('owner', function ($q){
                    $q->whereHas('meta', function ($q){
                        $q->where('key', UserMeta::ChannelAutoImportIsActive)->where('value', true);
                    });
                })
                ->inRandomOrder()
                ->take(1);

            if($channel->exists()){

                $channel->update([
                    'import_request_status' => Channel::IMPORT_STATUS_SYNC
                ]);

            }

        }

        return null;
    }

}

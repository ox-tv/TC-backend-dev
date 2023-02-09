<?php

namespace App\Console\Commands;

use App\Models\Channel;
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

    /**
     * Execute the console command.
     *
     * @return int
     */


    public function handle()
    {
        $isChannelImporting = Channel::where('import_request_status', Channel::IMPORT_STATUS_SYNC)->exists();

        if(!$isChannelImporting){

            $channel = Channel::where('status', Channel::STATUS_PUBLISHED)
                ->where('youtube_last_scraped_at', '<=', Carbon::now()->subHour())
                ->take(1)
                ->get();

            if(count($channel)){

                $channel->update([
                    'import_request_status' => Channel::IMPORT_STATUS_SYNC
                ]);

                return $channel;

            }

        }

        return null;
    }

}

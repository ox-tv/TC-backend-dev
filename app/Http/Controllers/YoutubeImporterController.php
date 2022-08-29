<?php

namespace App\Http\Controllers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Http\Requests\ChannelImportRequest;
use App\Http\Resources\Channel\ImportRequestResource;
use App\Models\Channel;
use Illuminate\Http\Request;

class YoutubeImporterController extends Controller
{
    public function index(Request $request)
    {
        $channels = Channel::whereIn('import_request_status', [Channel::IMPORT_STATUS_REQUESTED, Channel::IMPORT_STATUS_SYNC])->get();

        return ImportRequestResource::collection($channels);
    }


    public function importRequest(ChannelImportRequest $request, Channel $channel)
    {
        if (in_array($channel->import_request_status, [Channel::IMPORT_STATUS_COMPLETED, Channel::IMPORT_STATUS_SYNC])){
            return response()->json([
                'message' => __('channel.import_accept.bad_request'),
            ], 400);
        }

        $channel->import_request_status = Channel::IMPORT_STATUS_REQUESTED;
        $channel->youtube_channel_id = $request->get("youtube_channel_id");
        $channel->save();

        event(new ChannelImportRequestAccepted($channel));

        return response()->json([
            'message' => __('channel.messages.import_request_submitted'),
        ]);
    }

    public function importCompleted(Channel $channel)
    {
        if ($channel->import_request_status == Channel::IMPORT_STATUS_REQUESTED){

            $channel->import_request_status = Channel::IMPORT_STATUS_COMPLETED;
            $channel->save();

            event(new ChannelImportRequestCompleted($channel));

            return response()->json([
                'message' => __('channel.messages.import_completed'),
            ]);
        }

        return response()->json([
            'message' => __('channel.import_completed.bad_request'),
        ], 400);
    }

    public function updateChannel(Channel $channel, Request $request)
    {
        $request->validate([
            'youtube_last_scraped_at' => ['sometimes', 'date']
        ]);

        $channel->youtube_last_scraped_at = $request->get('youtube_last_scraped_at');
        $channel->save();

        return response()->json(['status' => 'ok']);
    }
}

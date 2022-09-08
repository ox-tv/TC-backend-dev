<?php

namespace App\Http\Controllers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Http\Requests\ChannelImportRequest;
use App\Http\Resources\Channel\ImportRequestResource;
use App\Http\Resources\Video\VideoResource;
use App\Libraries\YIClient;
use App\Models\Channel;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $channel->import_request_status = Channel::IMPORT_STATUS_COMPLETED;
        $channel->save();

        return response()->json(['status' => 'ok']);
    }

    public function storeVideo(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'description' => ['sometimes'],
            'published_at' => ['sometimes'],
            'file_url' => ['required'],
            'thumbnail' => ['required'],
            'user_id' => ['required'],
        ]);

        $video = new Video();

        $video->title = $request->get('title');
        $video->slug = Str::slug($request->get('title'));
        $video->description = $request->get('description');
        $video->published_at = $request->get('published_at');
        $video->file_url = $request->get('file_url');
        $video->thumbnail_url = $request->get('thumbnail');
        $video->user_id = $request->get('user_id');
        $video->status = Video::STATUS_DRAFT_YI;
        $video->media_type = Video::MEDIA_TYPE_VIDEO;

        $video->save();

        return VideoResource::make($video);
    }

    public function syncRequest(): \Illuminate\Http\JsonResponse
    {
        $user = auth('api')->user();
        $channel = $user->channel;

        if (!$channel){
            return response()->json(['message' => 'Channel is not found for this user'], 404);
        }

        if (!$channel->youtube_last_scraped_at){
            return response()->json(['message' => 'you must '], 403);
        }

        $channel->import_request_status = Channel::IMPORT_STATUS_SYNC;
        $channel->save();

        return response()->json(['status' => 'ok']);
    }

    public function importStats(): \Illuminate\Http\JsonResponse
    {
        $user = auth('api')->user();
        $channel = $user->channel;
        $result = [
            'status' => $channel->import_request_status_text
        ];

        if (in_array($channel->import_request_status, [Channel::IMPORT_STATUS_REQUESTED, Channel::IMPORT_STATUS_SYNC])){
            $YIClient = new YIClient();
            $response = $YIClient->getImportStats($channel->id);

            if ($response['success']){
                $result['total'] = $response['data']['total'];
                $result['synced'] = $response['data']['synced'];
            }
        }

        return response()->json($result);
    }
}

<?php

namespace App\Http\Controllers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\VideoCreated;
use App\Http\Requests\ChannelImportRequest;
use App\Http\Resources\Channel\ImportRequestResource;
use App\Http\Resources\Video\VideoResource;
use App\Libraries\YIClient;
use App\Models\Category;
use App\Models\Channel;
use App\Models\CryptoCurrency;
use App\Models\Language;
use App\Models\Subtitle;
use App\Models\Tag;
use App\Models\UserMeta;
use App\Models\Video;
use App\Repository\Eloquent\TagRepository;
use Done\Subtitles\Subtitles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'subtitles' => ['sometimes'],
            'tags' => ['sometimes'],
        ]);

        $tags = $request->get('tags');
        $cryptoCurrencyIDs = false;

        if($tags){
            $excludedTags = explode(",", config('yi.auto_import_excluded_tags_for_cryptocurrency'));

            $filteredTags = array_filter($tags, function ($value) use($excludedTags) {
                return !in_array($value, $excludedTags);
            });

            $cryptoCurrencyIDs = CryptoCurrency::
            select("id")
                ->where(function ($query) use ($filteredTags){
                    $query->whereIn('symbol', $filteredTags)
                        ->orWhereIn('name', $filteredTags);
                })
                ->where('order', '<', '1000000')
                ->pluck('id');
        }

        $video = new Video();

        $video->title = $request->get('title');
        $video->slug = Str::slug($request->get('title'));
        $video->description = $request->get('description');
        $video->published_at = $request->get('published_at');
        $video->file_url = $request->get('file_url');
        $video->thumbnail_url = $request->get('thumbnail');
        $video->user_id = $request->get('user_id');
        $video->status = Video::STATUS_PUBLISHED;
        $video->media_type = Video::MEDIA_TYPE_VIDEO;
        $video->upload_method = Video::UPLOAD_METHOD_YOUTUBE_AUTO_IMPORT;

        DB::transaction(function () use ($request, $video, $tags, $cryptoCurrencyIDs){

            $video->save();

//            // adding categories
//            if($request->get('categories')){
//                $video->categories()->saveMany(Category::whereIn('id', $request->get('categories'))->get());
//            }

            // adding crypto currencies
            if($cryptoCurrencyIDs){
                $video->crypto_currencies()->sync($cryptoCurrencyIDs);
            }

            // adding tags
            if($tags){
                $tags = collect($tags);

                $tags->map(function ($tag) use ($video){
                    $video->tags()->save(TagRepository::store([
                        'name' => $tag,
                        'status' => Tag::STATUS_PUBLISHED,
                        'creation_scope' => Tag::CREATION_SCOPE_IMPORTER,
                    ]));
                });
            }

        });



        if (!empty($request->get('subtitles'))){
            foreach ($request->get('subtitles') as $subtitle){
                $language = Language::where('code', $subtitle['language_code'])->firstOr(function () use ($subtitle) {
                    $language = new Language();
                    $language->code = $subtitle['language_code'];
                    $language->display_name = $subtitle['language_name'];
                    $language->save();
                    return $language;
                });

                $folder = "subtitles/{$video->url_hash}";
                if (!Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }
                $path = "{$folder}/{$subtitle['language_code']}.vtt";
                $subtitles = Subtitles::load($subtitle['text'], 'srt');
                $subtitles->save(Storage::disk('public')->path($path));

                Subtitle::UpdateOrCreate([
                    'video_id' => $video->id,
                    'language_id' => $language->id
                ],[
                        'file_path' => $path,
                        'original_path' => null
                    ]
                );
            }
        }

        event(new VideoCreated($video));

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

    public function toggleAutoImport(Request $request)
    {
        $request->validate([
            'active' => 'required|boolean'
        ]);

        $user = auth('api')->user();
        $channel = $user->channel;

        if (!$channel){
            return response()->json(['message' => 'Channel is not found for this user'], 404);
        }

        $user->meta()->updateOrCreate(
            ['key' => UserMeta::ChannelAutoImportIsActive],
            ['value' => $request->get('active'),]
        );

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

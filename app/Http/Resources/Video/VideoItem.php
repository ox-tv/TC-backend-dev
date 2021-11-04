<?php

namespace App\Http\Resources\Video;

use App\Http\Resources\Category\CategoryCollection;
use App\Http\Resources\Category\CategoryItem;
use App\Http\Resources\Category\CategoryMinimalCollection;
use App\Http\Resources\Category\CategoryMinimalItem;
use App\Http\Resources\Channel\ChannelMinimalItem;
use App\Http\Resources\CryptoCurrency\CryptoCurrencyItem;
use App\Http\Resources\Playlist\PlaylistMinimalCollection;
use App\Http\Resources\Playlist\PlaylistMinimalItem;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\Subtitle\SubtitleItem;
use App\Http\Resources\User\UserMinimalItem;
use App\Http\Resources\VideoCommentCollection;
use App\Http\Resources\VideoSummaryCollection;
use App\Models\Video;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class VideoItem extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $include = explode(',', $request->get('include', ''));

        $withUser = in_array('user', $include) || $this->relationLoaded('user');
        $withChannel = in_array('channel', $include) || $this->relationLoaded('channel');
        $withMainCategory = in_array('category', $include) || $this->relationLoaded('category');
        $withCategories = in_array('categories', $include) || $this->relationLoaded('categories');
        $withCryptoCurrencies = in_array('crypto_currencies', $include) || $this->relationLoaded('crypto_currencies');
        $withTags = in_array('tags', $include) || $this->relationLoaded('tags');
        $withPlaylists = in_array('playlists', $include) || $this->relationLoaded('playlists');
        $withReports = in_array('reports', $include) || $this->relationLoaded('reports');
        $withSubtitles = in_array('subtitles', $include) || $this->relationLoaded('subtitles');

        $user = ($withUser)? UserMinimalItem::make($this->user) : [];
        $channel = ($withChannel)? ChannelMinimalItem::make($this->channel) : [];
        $category = ($withMainCategory)? CategoryMinimalItem::make($this->category) : [];
        $categories = ($withCategories)? CategoryMinimalItem::collection($this->categories) : [];
        $crypto_currencies = ($withCryptoCurrencies)? CryptoCurrencyItem::collection($this->crypto_currencies) : [];
        $tags = ($withTags)? $this->tags : [];
        $playlists = ($withPlaylists)? PlaylistMinimalItem::collection($this->playlists) : [];
        $reports = ($withReports)? ReportMinimalItem::collection($this->reports) : [];
        $subtitles = ($withSubtitles)? SubtitleItem::collection($this->subtitles) : [];

        $url = '';
        if ($this->file_path){
            $url = Storage::disk('videos')->url($this->file_path);
        }elseif ($this->s3_url){
            $url = $this->s3_url;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'url_hash' => $this->url_hash,
            'description' => $this->description,
            'url' => $url,
            'thumbnail' => $this->thumbnail,
            'status' => Video::STATUS_TEXT[$this->status]?? null,
            'duration' => $this->duration,
            'user_id' => $this->user_id,
            'view_count' => $this->view_count,
            'category_id' => $this->category_id,
            'language' => $this->language,
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
            'is_published' => $this->is_published,
            'rating' => $this->rating,
            'comment_count' => $this->comment_count,
            'likes_count' => $this->likes_count,
            'dislikes_count' => $this->dislikes_count,
            'reports_count' => $this->reports_count,
            'is_liked' => $this->is_liked,
            'is_disliked' => $this->is_disliked,
            'is_bookmarked' => $this->is_bookmarked,
            'watch_time' => $this->watch_time,
            'reason_key' => $this->when($this->reason_key, $this->reason_key),
            'reason_text' => $this->when($this->reason_text, $this->reason_text),
            'user' => $this->when($withUser, $user),
            'channel' => $this->when($withChannel, $channel),
            'categories' => $this->when($withCategories, $categories),
            'crypto_currencies' => $this->when($withCryptoCurrencies, $crypto_currencies),
            'category' => $this->when($withMainCategory, $category),
            'tags' => $this->when($withTags, $tags),
            'playlists' => $this->when($withPlaylists, $playlists),
            'reports' => $this->when($withReports, $reports),
            'subtitles' => $this->when($withSubtitles, $subtitles),
        ];
    }
}

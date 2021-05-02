<?php

namespace App\Models;

use App\Models\Scopes\OrderDescScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Video extends Model
{
    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    const STATUS_ARCHIVED = 3;
    const STATUS_SUSPENDED = 4;
    const STATUS_HIDDEN = 5;

    const STATUS_TEXT = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_PUBLISHED => 'published',
        self::STATUS_ARCHIVED => 'archived',
        self::STATUS_SUSPENDED => 'suspended',
        self::STATUS_HIDDEN => 'hidden'
    ];

    const UPLOAD_METHOD_DIRECT = 1;
    const UPLOAD_METHOD_YOUTUBE = 2;

    use HasFactory;
    use SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope(new OrderDescScope);

        self::saved(function($model){
            if(is_null($model->url_hash) && !is_null($model->id)){
                $model->url_hash = encode_id(str_pad($model->id,10,0,STR_PAD_RIGHT));
                $model->save();
            }

            if(is_null($model->channels()->first()) && Auth::guard('api')->check()){
                // channel
                $user = User::find($model->user_id);
                $channel = $user->channel;

                if(is_null($channel)){
                    $channel = Channel::create([
                        'name' => $user->username ? $user->username : $user->email,
                        'user_id' => $user->id
                    ]);
                }

                $model->channels()->save($channel);
            }

            // duration
            if(is_null($model->duration) && !is_null($model->file_path)){
                $model->duration = get_duration($model->file_path);

                if($model->duration){
                    $model->save();
                }
            }

        });
    }

    public function scopeDraft($query){
        $query->where('status', self::STATUS_DRAFT);
        return $query;
    }

    public function scopePublished($query){
        $query->where('status', self::STATUS_PUBLISHED);
        return $query;
    }

    public function scopeArchived($query){
        $query->where('status', self::STATUS_ARCHIVED);
        return $query;
    }

    public function scopeSuspended($query){
        $query->where('status', self::STATUS_SUSPENDED);
        return $query;
    }

    public function scopeMine($query){
        if(auth('api')->check()){
            $query->where('user_id', auth('api')->id());
        }
        return $query;
    }

    public function scopeInChannel($query, $channelId){
        $channel = Channel::find($channelId);

        if($channel){
            $query->whereHas('channels', function($q) use ($channel){
                $q->where('id', $channel->id);
            });
        }else{
            $query->where('user_id', null);
        }

        return $query;

    }

    // filters by time

    public function scopeWeek($query){
        $query->whereBetween('published_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        return $query;
    }

    public function scopeMonth($query){
        $query->whereBetween('published_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        return $query;
    }

    // search scopes

    public function scopeSearchTitle($query, $keyword){
        $query->where('title', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchDescription($query, $keyword){
        $query->where('description', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    // category scope

    public function scopeFilterCategory($query, $categoryId){
        $query->whereHas('categories', function($q) use ($categoryId){
            $q->where('id', $categoryId);
        });
        return $query;
    }

    // playlist scope
    public function scopeInPlaylist($query, $playlistId){
        $query->whereHas('playlists', function($q) use ($playlistId){
            $q->where('id', $playlistId);
        });
        return $query;
    }


    // relations
    public function categories(){
        return $this->belongsToMany('App\Models\Category');
    }

    public function category(){
        return $this->belongsTo('App\Models\Category');
    }

    public function likedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', UserVideo::LIKED_RELATION);
    }

    public function dislikedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', UserVideo::DISLIKED_RELATION);
    }

    public function bookmarkedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->where('relation', UserVideo::BOOKMARKED_RELATION);
    }

    public function comments(){
        return $this->hasMany('App\Models\Comment')->whereNull('parent_id');
    }

    public function playlists(){
        return $this->belongsToMany('App\Models\Playlist');
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function channels(){
        return $this->belongsToMany('App\Models\Channel');
    }

    public function tags(){
        return $this->belongsToMany('App\Models\Tag');
    }

    public function views(){
        return $this->hasMany('App\Models\VideoView');
    }


    // Attributes
    public function getRatingAttribute(){
        return UserVideo::where('video_id', $this->id)->sum('relation');
    }

    public function getIsPublishedAttribute(){
        return $this->status == self::STATUS_PUBLISHED;
    }

    public function getIsMineAttribute(){
        return auth('api')->check() ? ($this->user_id ==  auth('api')->user()->id) : false;
    }

    public function getIsLikedAttribute(){
        if(auth('api')->check()){
            if($this->likedBy()->find(auth('api')->user()->id)){
                return true;
            }
        }

        return false;
    }

    public function getIsDislikedAttribute(){
        if(auth('api')->check()){
            if($this->dislikedBy()->find(auth('api')->user()->id)){
                return true;
            }
        }

        return false;
    }

    public function getIsBookmarkedAttribute(){
        if(auth('api')->check()){
            if($this->bookmarkedBy()->find(auth('api')->user()->id)){
                return true;
            }
        }

        return false;
    }

    public function getPublishedAtAttribute(){
        return $this->created_at;
    }

    public function getRelatedVideosAttribute(){
        $tags = $this->tags->pluck('id')->toArray();


        $relatedVideos = collect();

        if( count($tags) ){
            $relatedVideosByTag = Video::published()->whereHas('tags', function($q) use ($tags){
                $q->whereIn('id', $tags);
            })->get();

            $relatedVideos = $relatedVideos->merge($relatedVideosByTag);
        }

        if( count($relatedVideos) < 15 ){
            $secondaryCategories = $this->categories->pluck('id')->toArray();

            $relatedVideosBySecondaryCategories = Video::published()->whereHas('categories', function($q) use ($secondaryCategories){
                $q->whereIn('id', $secondaryCategories);
            })->get();

            $relatedVideos = $relatedVideos->merge($relatedVideosBySecondaryCategories);
        }

        if( count($relatedVideos) < 15 ){
            $category = $this->category ? $this->category->id : null;

            $relatedVideosByCategory = Video::published()->whereHas('categories', function($q) use ($category){
                $q->where('id', $category);
            })->get();

            $relatedVideos = $relatedVideos->merge($relatedVideosByCategory);
        }

        $relatedVideos = $relatedVideos->unique();

        return $relatedVideos;

    }


}

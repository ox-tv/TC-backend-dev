<?php

namespace App\Models;

use App\Models\Scopes\OrderDescScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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

    const MEDIA_TYPE_VIDEO = 1;
    const MEDIA_TYPE_PODCAST = 2;

    const MEDIA_TYPE_TEXT = [
        self::MEDIA_TYPE_VIDEO => 'video',
        self::MEDIA_TYPE_PODCAST => 'podcast',
    ];

    const FILE_TYPE_VIDEO = 'video';
    const FILE_TYPE_AUDIO = 'audio';

    const UPLOAD_METHOD_DIRECT = 1;
    const UPLOAD_METHOD_YOUTUBE = 2;

    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'published_at' => 'datetime'
    ];

    protected $attributes = [
        'media_type' => self::MEDIA_TYPE_VIDEO,
    ];

    protected static function booted()
    {
        static::addGlobalScope(new OrderDescScope);

        self::saved(function($model){
            if(is_null($model->url_hash) && !is_null($model->id)){
                $model->url_hash = encode_id(str_pad($model->id,10,0,STR_PAD_RIGHT));
                $model->save();
            }

            if(empty($model->channel_id) && auth('api')->check()){
                // channel
                $user = User::find($model->user_id);
                $channel = $user->channel;

                if(is_null($channel)){
                    $channel = Channel::create([
                        'name' => $user->username ? : $user->email,
                        'user_id' => $user->id
                    ]);
                }

                $model->channel_id = $channel->id;
                $model->save();
            }

            // duration
            if(is_null($model->duration) && (!is_null($model->file_path) || !is_null($model->file_url))){
                $path = !empty($model->file_url)? $model->file_url: Storage::disk('videos')->path($model->file_path);
                $model->duration = get_duration($path);

                if($model->duration){
                    $model->save();
                }
            }

            if (empty($model->published_at) && $model->status == self::STATUS_PUBLISHED){
                $model->published_at = now();
                $model->save();
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

    public function scopeTypeVideo($query){
        $query->where('media_type', self::MEDIA_TYPE_VIDEO);
        return $query;
    }

    public function scopeTypePodcast($query){
        $query->where('media_type', self::MEDIA_TYPE_PODCAST);
        return $query;
    }

    public function scopePublishedOrMine($query){
        $query->where(function ($query) {
            $query->where('status', self::STATUS_PUBLISHED);

            if(auth('api')->check()){
                $query->orWhere('user_id', auth('api')->id());
            }
        });
        return $query;
    }

    public function scopeIdOrUrlHash($query, $idOrUrlHash){
        $query->where(function ($query) use ($idOrUrlHash){
            $query->where('id', $idOrUrlHash)->orWhere('url_hash', $idOrUrlHash);
        });
        return $query;
    }

    public function scopePublishedOnceWithTrashed($query){
        $query->withTrashed()->whereNotNull('published_at');
        return $query;
    }

    public function scopeInChannel($query, $channelId){
        $query->where('channel_id', $channelId);
        return $query;
    }

    // filters by time

    public function scopeLastHour($query){
        $query->where('published_at', '>=', Carbon::now()->subHour());
        return $query;
    }

    public function scopeLastDay($query){
        $query->where('published_at', '>=', Carbon::now()->subDay());
        return $query;
    }

    public function scopeLastWeek($query){
        $query->where('published_at', '>=', Carbon::now()->subWeek());
        return $query;
    }

    public function scopeLastMonth($query){
        $query->where('published_at', '>=', Carbon::now()->subMonth());
        return $query;
    }

    public function scopeLastSeason($query){
        $query->where('published_at', '>=', Carbon::now()->subMonths(3));
        return $query;
    }

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
        /*$query->whereHas('categories', function($q) use ($categoryId){
            $q->where('id', $categoryId);
        });*/
        $query->where('category_id', $categoryId);
        return $query;
    }

    public function scopeFilterCryptoCurrency($query, $cryptoCurrencyId){
        $query->whereHas('crypto_currencies', function($q) use ($cryptoCurrencyId){
            $q->where('crypto_currencies.id', $cryptoCurrencyId);
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

    public function crypto_currencies(){
        return $this->belongsToMany('App\Models\CryptoCurrency', 'crypto_currency_video');
    }

    public function category(){
        return $this->belongsTo('App\Models\Category');
    }

    public function language(){
        return $this->belongsTo('App\Models\Language');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, "reportable");
    }

    public function subtitles()
    {
        return $this->hasMany(Subtitle::class);
    }

    public function likedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->withTimestamps()->where('relation', UserVideo::LIKED_RELATION);
    }

    public function dislikedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->withTimestamps()->where('relation', UserVideo::DISLIKED_RELATION);
    }

    public function bookmarkedBy(){
        return $this->belongsToMany('App\Models\User')->withPivot('relation')->withTimestamps()->where('relation', UserVideo::BOOKMARKED_RELATION);
    }

    public function comments(){
        return $this->hasMany('App\Models\Comment')->withoutGlobalScope(OrderDescScope::class)->orderByDesc("is_pinned")->orderByDesc("created_at");
    }

    public function chapters(){
        return $this->hasMany('App\Models\Chapter');
    }

    public function playlists(){
        return $this->belongsToMany('App\Models\Playlist');
    }

    public function user(){
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    public function channels(){
        return $this->belongsToMany('App\Models\Channel');
    }

    public function channel(){
        return $this->belongsTo('App\Models\Channel')->withTrashed();
    }

    public function tags(){
        return $this->belongsToMany('App\Models\Tag');
    }

    public function views(){
        return $this->hasMany('App\Models\VideoView');
    }

    public function watch_times(){
        return $this->belongsToMany('App\Models\User', "watch_times")->withTimestamps()->withPivot(["start_time","end_time"]);
    }

    public function meta(){
        return $this->hasMany('App\Models\VideoMeta');
    }

    /*public function layers(){
        return $this->hasOne('App\Models\VideoMeta')->where('key', VideoMeta::VIDEO_LAYERS);
    }

    public function layersDraft(){
        return $this->hasOne('App\Models\VideoMeta')->where('key', VideoMeta::VIDEO_LAYERS_DRAFT);
    }*/

    public function dailyStatistics(){
        return $this->hasMany('App\Models\VideoStatisticsDaily');
    }


    // Attributes
    public function getLayersAttribute()
    {
        $meta = $this->meta()->where('key', 'layers')->first();
        return $meta? $meta->value : null;
    }

    public function getRatingAttribute(){
        return UserVideo::where('video_id', $this->id)
            ->whereIn("relation",[UserVideo::LIKED_RELATION, UserVideo::DISLIKED_RELATION])
            ->sum('relation');
    }

    public function getCommentCountAttribute(){
        return $this->comments()->count();
    }

    public function getLikesCountAttribute(){
        return $this->likedBy()->count();
    }

    public function getDislikesCountAttribute(){
        return $this->dislikedBy()->count();
    }

    public function getIsPublishedAttribute(){
        return $this->status == self::STATUS_PUBLISHED;
    }

    public function getReportsCountAttribute(){
        return $this->reports()->count();
    }

    public function getIsMineAttribute(){
        return auth('api')->check() && $this->user_id == auth('api')->user()->id;
    }

    public function getIsLikedAttribute(){
        return auth('api')->check()
            && UserVideo::where([
                "user_id" => auth('api')->id(),
                "video_id" => $this->id,
                "relation" => UserVideo::LIKED_RELATION
            ])->exists();
    }

    public function getIsDislikedAttribute()
    {
        return auth('api')->check()
            && UserVideo::where([
                "user_id" => auth('api')->id(),
                "video_id" => $this->id,
                "relation" => UserVideo::DISLIKED_RELATION
            ])->exists();
    }

    public function getIsBookmarkedAttribute()
    {
        return auth('api')->check()
            && UserVideo::where([
                "user_id" => auth('api')->id(),
                "video_id" => $this->id,
                "relation" => UserVideo::BOOKMARKED_RELATION
            ])->exists();
    }

    public function getStatusTextAttribute()
    {
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }

    public function getFileUrlAttribute($value)
    {
        return $value? : Storage::disk('videos')->url($this->file_path);
    }

    public function getThumbnailUrlAttribute($value)
    {
        return $value? :$this->thumbnail;
    }

    public function getThumbnailsAttribute()
    {
        return $this->thumbnail_url? getThumbnails($this->thumbnail_url):[];
    }

    public function getMediaTypeTextAttribute()
    {
        return self::MEDIA_TYPE_TEXT[$this->media_type]?? $this->media_type;
    }

    public function getPinnedCommentAttribute()
    {
        return $this->comments()->onlyParent()->where('is_pinned', true)->first();
    }

    public function getFileTypeAttribute()
    {
        $extention = strtolower(pathinfo($this->file_url, PATHINFO_EXTENSION));

        return in_array($extention, ['mp4', 'mov', 'webm'])?
            self::FILE_TYPE_VIDEO:
            self::FILE_TYPE_AUDIO;
    }

}

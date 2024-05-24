<?php

namespace App\Models;

use App\Repository\Eloquent\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Channel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name', 'user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'youtube_last_scraped_at' => 'datetime',
        'monetization_qualified_at' => 'datetime',
    ];

    protected $attributes = [
        'import_request_status' => self::IMPORT_STATUS_OFF,
    ];

    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    const STATUS_FREEZE= 5;

    const STATUS_TEXT = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_PUBLISHED => 'published',
        self::STATUS_FREEZE => 'freeze',
    ];


    const IMPORT_STATUS_REQUESTED = 1;
    const IMPORT_STATUS_COMPLETED = 2;
    const IMPORT_STATUS_OFF = 3;
    const IMPORT_STATUS_SYNC = 4;

    const IMPORT_STATUS_TEXT = [
        self::IMPORT_STATUS_REQUESTED => 'requested',
        self::IMPORT_STATUS_COMPLETED => 'completed',
        self::IMPORT_STATUS_OFF => 'off',
        self::IMPORT_STATUS_SYNC => 'sync',
    ];


    protected static function booted()
    {
        self::saved(function($model){

            // change owner mute by channel status
            $owner = $model->owner()->withTrashed()->first();
            $owner->muted_until = null;

            if($model->status == self::STATUS_FREEZE){
                $owner->is_mute = true;
            }else{
                $owner->is_mute = false;
            }
            $owner->save();

        });
    }


    // Scopes

    public function scopeDraft($query){
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query){
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeFreeze($query){
        return $query->where('status', self::STATUS_FREEZE);
    }

    public function scopeMine($query){
        if(Auth::check()){
            return $query->where('user_id', Auth::user()->id);
        }

        return $query;
    }

    public function scopeIdOrSlug($query, $idOrSlug){
        $query->where(function ($query) use ($idOrSlug){
            $query->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
        });
        return $query;
    }

    public function scopeSearchTitle($query, $keyword){
        $query->where('name', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchByOwner($query, $keyword){

        $usersIds = User::where('username', 'LIKE', '%'.$keyword.'%')->orWhere('email', 'LIKE', '%'.$keyword.'%')->select('id')->pluck('id')->toArray();

        $query->whereIn('user_id', $usersIds);

        return $query;
    }

    // filters by time

    public function scopeWeek($query){
        $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        return $query;
    }

    public function scopeMonth($query){
        $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        return $query;
    }

    public function scopeYear($query){
        $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
        return $query;
    }


    // Relations

    public function owner(){
        return $this->belongsTo('App\Models\User', 'user_id')->withTrashed();
    }

    public function videos(){
        return $this->hasMany('App\Models\Video');
    }

    public function subscribers(){
        return $this->belongsToMany('App\Models\User', 'channel_user', 'channel_id')->withTimestamps();
    }

    public function heroSubscribers(){
        return $this->belongsToMany('App\Models\User', 'channel_user', 'channel_id')->withTimestamps()
            ->whereDate('hero_due_at', '>=', Carbon::now());
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, Video::class);
    }

    public function language(){
        return $this->belongsTo('App\Models\Language');
    }


    // Attribute

    public function getWatchTimeAttribute(){
        return $this->videos()->sum("watch_time");
    }

    public function getUploadsCountAttribute(){
        return $this->videos()->count();
    }

    public function getTotalViewsAttribute()
    {
        return $this->videos()->sum("view_count");
    }

    public function getTotalLikesAttribute()
    {
        return Channel2StatisticsDaily::where('channel_id', $this->id)->sum('likes_total');
    }

    public function getTotalDislikesAttribute()
    {
        return Channel2StatisticsDaily::where('channel_id', $this->id)->sum('dislikes_total');
    }

    public function getTotalCommentsAttribute()
    {
        $video_ids = $this->videos()->pluck('id');

        return Comment::whereIn('video_id', $video_ids)->count();
    }

    public function getIsSubscribedAttribute()
    {
        $repository = new UserRepository();

        return auth('api')->check()
            && in_array($this->id, $repository->subscribedChannelIds(auth('api')->id()));
    }

    public function getSubscribersCountAttribute()
    {
        return $this->subscribers()->count();
    }

    public function getHeroSubscribersCountAttribute()
    {
        return $this->heroSubscribers()->count();
    }

    public function getReferralsCountAttribute()
    {
        $owner = $this->owner()->first();
        return $owner? $owner->referrals()->count() : 0;
    }

    public function getStatusTextAttribute(){
        return self::STATUS_TEXT[$this->status]?? $this->status;
    }

    public function getImportRequestStatusTextAttribute(){
        return self::IMPORT_STATUS_TEXT[$this->import_request_status]?? $this->import_request_status;
    }

    public function getYoutubeNextScrapAtAttribute(){
        return $this->youtube_last_scraped_at? $this->youtube_last_scraped_at->addHours(config('yi.auto_import_frequency')) : null;
    }

    public function getAvatarAttribute($value)
    {
        return $this->avatar_url? : $value;
        //return $this->avatar_url? (strpos($this->avatar_url, 'cloudflarestorage') !== false? getR2TemporaryUrl($this->avatar_url) : $this->avatar_url) : $value;
    }

    public function getAvatarThumbnailsAttribute()
    {
        if (!$this->attributes['avatar_url']){
            return [];
        }

        foreach ($urls = getThumbnails($this->attributes['avatar_url']) as $key => $value){
            $urls[$key] = $value;
            //$urls[$key] = strpos($value, 'cloudflarestorage') !== false? getR2TemporaryUrl($value) : $value;
        }

        return $urls;
    }

    public function getCoverAttribute($value)
    {
        return $this->cover_url? : $value;
        //return $this->cover_url? (strpos($this->cover_url, 'cloudflarestorage') !== false? getR2TemporaryUrl($this->cover_url) : $this->cover_url) : $value;
    }

    public function getCoverThumbnailsAttribute()
    {
        if (!$this->attributes['cover_url']){
            return [];
        }

        foreach ($urls = getThumbnails($this->attributes['cover_url']) as $key => $value){
            $urls[$key] = $value;
            //$urls[$key] = strpos($value, 'cloudflarestorage') !== false? getR2TemporaryUrl($value) : $value;
        }

        return $urls;
    }


    // Mutators
    public function setAvatarUrlAttribute($value)
    {
        $this->attributes['avatar_url'] = explode('?', $value)[0];
    }

    public function setCoverUrlAttribute($value)
    {
        $this->attributes['Cover_url'] = explode('?', $value)[0];
    }
}

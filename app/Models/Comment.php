<?php

namespace App\Models;

use App\Models\Scopes\OrderDescScope;
use App\Models\Scopes\WhereParentNullScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    const COMMENT_PINNED = 1;
    const COMMENT_NOT_PINNED = 0;

    protected $casts = [
      'is_pinned' => 'boolean',
      'read_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new OrderDescScope);
        static::addGlobalScope(new WhereParentNullScope);
    }

    // scopes

    public function scopeHasVideo($query){
        $query->whereHas('video');
        return $query;
    }

    public function scopeInVideos($query, $videos){
        if(is_array($videos) && count($videos)>0){
            return $query->whereIn('video_id', $videos);
        }
        return $query;
    }

    public function scopeLastHour($query){
        $query->where('created_at', '>=', Carbon::now()->subHour());
        return $query;
    }

    public function scopeLastDay($query){
        $query->where('created_at', '>=', Carbon::now()->subDay());
        return $query;
    }

    public function scopeLastWeek($query){
        $query->where('created_at', '>=', Carbon::now()->subWeek());
        return $query;
    }

    public function scopeLastMonth($query){
        $query->where('created_at', '>=', Carbon::now()->subMonth());
        return $query;
    }

    public function scopeLastSeason($query){
        $query->where('created_at', '>=', Carbon::now()->subMonths(3));
        return $query;
    }

    // Relations

    public function video(){
        return $this->belongsTo('App\Models\Video')->withTrashed();
    }

    public function user(){
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    public function PinnedBy(){
        return $this->belongsTo('App\Models\User', 'pinned_by')->withTrashed();
    }

    public function parent(){
        return $this->hasOne('App\Models\Comment', 'parent_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, "reportable");
    }

    public function likedBy(){
        return $this->belongsToMany('App\Models\User')->withTimestamps()->withPivot('relation')->where('relation', CommentUser::LIKED_RELATION);
    }

    public function dislikedBy(){
        return $this->belongsToMany('App\Models\User')->withTimestamps()->withPivot('relation')->where('relation', CommentUser::DISLIKED_RELATION);
    }

    public function rememberedBy(){
        return $this->belongsToMany('App\Models\User')->withTimestamps()->withPivot('relation')->where('relation', CommentUser::REMEMBERED_RELATION);
    }

    public function mentions(){
        return $this->belongsToMany('App\Models\User')->withTimestamps()->withPivot('relation')->where('relation', CommentUser::MENTION_RELATION);
    }

    public function replies(){
        return $this->hasMany('App\Models\Comment', 'parent_id', 'id')->withoutGlobalScope(WhereParentNullScope::class);
    }


    // Attributes

    public function setTextAttribute($value)
    {
        $this->attributes['text'] = preg_replace("/([\n][\n][\n]+)/", "\n\n", $value);;
    }

    public function getIsDislikedAttribute()
    {
        if(auth('api')->check()){
            return CommentUser::where('comment_id', $this->id)
                ->where('user_id', auth('api')->id())
                ->where('relation', CommentUser::DISLIKED_RELATION)->exists();
        }

        return false;
    }

    public function getIsLikedAttribute()
    {
        if(auth('api')->check()){
            return CommentUser::where('comment_id', $this->id)
                ->where('user_id', auth('api')->id())
                ->where('relation', CommentUser::LIKED_RELATION)->exists();
        }

        return false;
    }

    public function getIsRememberedAttribute()
    {
        if(auth('api')->check()){
            return $this->rememberedBy()->whereUserId(auth('api')->id())->exists();
        }

        return false;
    }

    public function getReportsCountAttribute()
    {
        return $this->reports()->count();
    }

    public function getLikesCountAttribute()
    {
        return $this->likedBy()->count();
    }

    public function getDislikesCountAttribute()
    {
        return $this->dislikedBy()->count();
    }

    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }

    public function getIsPinnedAttribute($value)
    {
        return (bool) $value;
    }

    public function getIsReadRepliesAttribute(): bool
    {
        return !self::where('parent_id', $this->id)->withoutGlobalScope(WhereParentNullScope::class)->whereNull('read_at')->exists();
    }
}

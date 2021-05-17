<?php

namespace App\Models;

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

    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    const STATUS_ARCHIVED = 3;
    const STATUS_SUSPENDED = 4;
    const STATUS_FREEZE= 5;

    const STATUS_TEXT = [
        self::STATUS_DRAFT => 'draft',
        self::STATUS_PUBLISHED => 'published',
        self::STATUS_ARCHIVED => 'archived',
        self::STATUS_SUSPENDED => 'suspended',
        self::STATUS_FREEZE => 'freeze',
    ];


    const IMPORT_STATUS_REQUESTED = 1;
    const IMPORT_STATUS_COMPLETED = 2;

    const IMPORT_STATUS_TEXT = [
        self::IMPORT_STATUS_REQUESTED => 'requested',
        self::IMPORT_STATUS_COMPLETED => 'completed',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes

    public function scopeDraft($query){
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query){
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeArchived($query){
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeSuspended($query){
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeMine($query){
        if(Auth::check()){
            return $query->where('user_id', Auth::user()->id);
        }

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

    // search scopes

    public function scopeSearchTitle($query, $keyword){
        $query->where('name', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchByOwner($query, $keyword){

        $usersIds = User::where('username', 'LIKE', '%'.$keyword.'%')->orWhere('email', 'LIKE', '%'.$keyword.'%')->select('id')->pluck('id')->toArray();

        $query->whereIn('user_id', $usersIds);

        return $query;
    }


    // Relations

    public function owner(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function videos(){
        return $this->belongsToMany('App\Models\Video');
    }

    public function subscribers(){
        return $this->belongsToMany('App\Models\User', 'channel_user', 'channel_id');
    }

    public function heroSubscribers(){
        return $this->belongsToMany('App\Models\User', 'channel_user', 'channel_id')
            ->whereDate('hero_due_at', '>=', Carbon::now());
    }

    // Attribute

    public function getUploadsCountAttribute(){
        return $this->videos()->count();
    }

    public function getTotalViewsAttribute(){
        $totalViews = 0;

        $videos = $this->videos;

        foreach ($videos as $video){
            $totalViews += $video->view_count;
        }

        return $totalViews;
    }

    public function getTotalLikesAttribute(){
        $totalLikes = 0;

        $videos = $this->videos;

        foreach ($videos as $video){
            $totalLikes += $video->likedBy()->count();
        }

        return $totalLikes;
    }

    public function getTotalDislikesAttribute(){
        $totalDislikes = 0;

        $videos = $this->videos;

        foreach ($videos as $video){
            $totalDislikes += $video->dislikedBy()->count();
        }

        return $totalDislikes;
    }

    public function getTotalCommentsAttribute(){
        $totalDislikes = 0;

        $videos = $this->videos;

        foreach ($videos as $video){
            $totalDislikes += $video->comments()->count();
        }

        return $totalDislikes;
    }


}

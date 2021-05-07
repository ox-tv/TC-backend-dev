<?php

namespace App\Models;

use Amir\Permission\Models\Role;
use Amir\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;


    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 2;

    // roles
    const ADMIN_ROLE = 'admin';
    const PUBLISHER_ROLE = 'publisher';

    // mute durations
    const MUTE_1WEEK = 604800;
    const MUTE_2WEEK = 1209600;
    const MUTE_1MONTH = 2592000;
    const MUTE_3MONTH = 7776000;
    const MUTE_6MONTH = 15552000;
    const MUTE_1YEAR = 31104000;
    const MUTE_PERMANENT = 0;

    const MUTED_UNTIL_TEXT = [
        self::MUTE_1WEEK => '1_week',
        self::MUTE_2WEEK => '2_week',
        self::MUTE_1MONTH => '1_month',
        self::MUTE_3MONTH => '3_month',
        self::MUTE_6MONTH => '6_month',
        self::MUTE_1YEAR => '1_year',
        self::MUTE_PERMANENT => 'permanent',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'hero_member_at' => 'datetime',
        'hero_due_at' => 'datetime',
    ];

    // search scopes

    public function scopeSearchUsername($query, $keyword){
        $query->where('username', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeSearchEmail($query, $keyword){
        $query->where('email', 'LIKE', '%'.$keyword.'%');
        return $query;
    }

    public function scopeIsHero($query){
        $query->where('hero_due_at', '>', now());
        return $query;
    }

    public function scopeIsNotHero($query){
        $query->where(function ($query) {
            $query->whereNull('hero_due_at')
                ->orWhere('hero_due_at', '<=', now());
        });
        return $query;
    }

    // roles scopes

    public function scopePublishers($query){
        $publisherRoleId = Role::firstOrCreate(['name' => self::PUBLISHER_ROLE])->id;
        $query->where('role_id', $publisherRoleId);
        return $query;
    }

    public function scopeNotPublishers($query){
        $publisherRoleId = Role::firstOrCreate(['name' => self::PUBLISHER_ROLE])->id;
        $query->where('role_id', "<>", $publisherRoleId);
        return $query;
    }

    public function scopeAdmins($query){
        $adminRoleId = Role::firstOrCreate(['name' => self::ADMIN_ROLE])->id;
        $query->where('role_id', $adminRoleId);
        return $query;
    }

    public function scopeUsers($query){
        $query->where('role_id', null);
        return $query;
    }

    // Relations

    public function channel(){
        return $this->hasOne('App\Models\Channel', 'user_id');
    }

    public function subscribedChannels(){
        return $this->belongsToMany('App\Models\Channel', 'channel_user', 'user_id');
    }

    public function messages(){
        return $this->belongsToMany('App\Models\Message');
    }

    public function comments(){
        return $this->hasMany('App\Models\Comment');
    }

    public function likedVideos(){
        return $this->belongsToMany('App\Models\Video')->withPivot('relation')->where('relation', UserVideo::LIKED_RELATION);
    }

    public function dislikedVideos(){
        return $this->belongsToMany('App\Models\Video')->withPivot('relation')->where('relation', UserVideo::DISLIKED_RELATION);
    }

    public function bookmarkVideos(){
        return $this->belongsToMany('App\Models\Video')->withPivot('relation')->where('relation', UserVideo::BOOKMARKED_RELATION);
    }


    // Attributes
    public function getIsHeroAttribute(){
        return $this->hero_due_at > now();
    }

    public function getIsMuteAttribute($value){
        return $value && (empty($this->muted_until) || $this->muted_until > now());
    }

    public function getIsAdminAttribute(){
        $adminRoleId = Role::firstOrCreate(['name' => self::ADMIN_ROLE])->id;

        return $this->role_id == $adminRoleId;
    }
}

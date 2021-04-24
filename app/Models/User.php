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

    // roles scopes

    public function scopePublishers($query){
        $publisherRoleId = Role::firstOrCreate(['name' => self::PUBLISHER_ROLE])->id;
        $query->where('role_id', $publisherRoleId);
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

    public function getIsHeroAttribute(){
        return $this->hero_due_at > now();
    }

    public function getIsMuteAttribute(){
        return $this->muted_until > now();
    }
}

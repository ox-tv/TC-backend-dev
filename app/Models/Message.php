<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    // type field values
    const TYPE_WARNING = 1;

    const TYPE_TEXT = [
        self::TYPE_WARNING => 'warning',
    ];

    // user group field values
    const USER_GROUP_CUSTOM = 1;
    const USER_GROUP_ALL = 2;
    const USER_GROUP_PUBLISHER = 3;
    const USER_GROUP_HERO = 4;
    const USER_GROUP_NON_HERO = 5;

    const USER_GROUP_TEXT = [
        self::USER_GROUP_CUSTOM => 'custom',
        self::USER_GROUP_ALL => 'all',
        self::USER_GROUP_PUBLISHER => 'publisher',
        self::USER_GROUP_HERO => 'hero',
        self::USER_GROUP_NON_HERO => 'non-hero',
    ];


    // search scopes

    public function scopeMine($query){
        if(auth('api')->check()){
            $message_ids = MessageUser::where([
                "user_id" => auth("api")->id()
            ])->pluck("message_id");

            $query->where(function ($query) use ($message_ids){
                $query->where('user_id', auth('api')->id())
                    ->orWhereIn('id', $message_ids);
            });
        }
        return $query;
    }

    public function scopeDepartment($query, $departmentId){
        $query->where('department_id', $departmentId);
        return $query;
    }

    public function scopeNullParent($query){
        return $query->whereNull('parent_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function department(){
        return $this->belongsTo('App\Models\Department');
    }

    public function parent(){
        return $this->belongsTo('App\Models\Message', 'parent_id');
    }

    public function replies(){
        return $this->hasMany('App\Models\Message', 'parent_id');
    }
}

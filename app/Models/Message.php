<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_NEW = 1;
    const STATUS_VIEWED = 2;
    const STATUS_ANSWERED = 3;

    const STATUS_TEXT = [
        self::STATUS_NEW => 'new',
        self::STATUS_VIEWED => 'viewed',
        self::STATUS_ANSWERED => 'answered',
    ];


    // search scopes

    public function scopeMine($query){
        if(auth('api')->check()){
            $query->where('user_id', auth('api')->user()->id);
        }
        return $query;
    }

    public function scopeDepartment($query, $departmentId){
        $query->where('department_id', $departmentId);
        return $query;
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function department(){
        return $this->belongsTo('App\Models\Department');
    }


}

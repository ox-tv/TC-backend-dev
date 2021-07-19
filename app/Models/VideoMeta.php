<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoMeta extends Model
{
    protected $fillable = ['key', 'value'];

    protected $table = 'video_meta';
    public $timestamps = false;

    public function video(){
        return $this->belongsTo('App\Models\Video');
    }
}

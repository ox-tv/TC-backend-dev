<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subtitle extends Model
{
    protected $fillable = ['file_path', 'video_id', 'language_id'];

    // Relations
    public function video(){
        return $this->belongsTo('App\Models\Video');
    }

    public function language(){
        return $this->belongsTo('App\Models\Language');
    }
}

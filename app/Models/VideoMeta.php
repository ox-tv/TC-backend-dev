<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoMeta extends Model
{
    protected $fillable = ['key', 'value'];

    protected $table = 'video_meta';

    const VIDEO_LAYERS = 'layers';
    const VIDEO_LAYERS_DRAFT = 'layers_draft';

    const KEYS_WITH_JSON_VALUE = [
        self::VIDEO_LAYERS,
        self::VIDEO_LAYERS_DRAFT,
    ];

    // Relations
    public function video(){
        return $this->belongsTo('App\Models\Video');
    }

    // Attributes
    public function getValueAttribute($value){
        if (in_array($this->key, self::KEYS_WITH_JSON_VALUE)){
            return json_decode($value, true);
        }
        return $value;
    }
}

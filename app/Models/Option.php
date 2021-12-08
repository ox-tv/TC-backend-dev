<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use function JmesPath\search;

class Option extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    // Define Keys
    const REPORT_VIDEO_REASONS = 'report_video_reasons';
    const REPORT_COMMENT_REASONS = 'report_comment_reasons';
    const HIDE_VIDEO_REASONS = 'video_hide_reasons';
    const DELETE_VIDEO_REASONS = 'video_delete_reasons';
    const DELETE_COMMENT_REASONS = 'comment_delete_reasons';
    const REJECT_PUBLISHER_REQUEST_REASONS = 'publisher_request_reject_reasons';

    const REASONS = [
        self::REPORT_VIDEO_REASONS,
        self::REPORT_COMMENT_REASONS,
        self::HIDE_VIDEO_REASONS,
        self::DELETE_VIDEO_REASONS,
        self::DELETE_COMMENT_REASONS,
        self::REJECT_PUBLISHER_REQUEST_REASONS,
    ];

    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function get($key)
    {
        return self::where("key", $key)->first()->value ?? null;
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    // Define Keys
    const VIDEO_REPORT_REASONS = 'video_report_reasons';
    const COMMENT_REPORT_REASONS = 'comment_report_reasons';
    const VIDEO_HIDE_REASONS = 'video_hide_reasons';
    const VIDEO_DELETE_REASONS = 'video_delete_reasons';
    const COMMENT_DELETE_REASONS = 'comment_delete_reasons';
    const PUBLISHER_REQUEST_REJECT_REASONS = 'publisher_request_reject_reasons';
    const TOTAL_DISTRIBUTED_MONEY = 'total_distributed_money';
    const FORBIDDEN_WORDS = 'forbidden_words';
    const AD_SPACES = 'ad_spaces';

    const REASONS = [
        self::VIDEO_REPORT_REASONS,
        self::COMMENT_REPORT_REASONS,
        self::VIDEO_HIDE_REASONS,
        self::VIDEO_DELETE_REASONS,
        self::COMMENT_DELETE_REASONS,
        self::PUBLISHER_REQUEST_REJECT_REASONS,
    ];

    const REASONS_STATUS_ACTIVE = 'active';
    const REASONS_STATUS_INACTIVE = 'inactive';

    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function get($key)
    {
        return self::where("key", $key)->first();
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    // Define Keys
    const KEY_REPORT_VIDEO_REASONS = 'report_video_reasons';
    const KEY_REPORT_COMMENT_REASONS = 'report_comment_reasons';
    const KEY_HIDE_VIDEO_REASONS = 'video_hide_reasons';
    const KEY_DELETE_VIDEO_REASONS = 'video_delete_reasons';
    const KEY_DELETE_COMMENT_REASONS = 'comment_delete_reasons';
    const KEY_REJECT_PUBLISHER_REQUEST_REASONS = 'publisher_request_reject_reasons';

}

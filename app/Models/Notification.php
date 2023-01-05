<?php

namespace App\Models;

use App\Models\Scopes\WherePublishedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];

    // user group field values
    const USER_GROUP_CUSTOM = 1;
    const USER_GROUP_ALL = 2;
    const USER_GROUP_HERO = 3;
    const USER_GROUP_NON_HERO = 4;

    const USER_GROUP_TEXT = [
        self::USER_GROUP_CUSTOM => 'custom',
        self::USER_GROUP_ALL => 'all',
        self::USER_GROUP_HERO => 'hero',
        self::USER_GROUP_NON_HERO => 'non-hero',
    ];

    // Scope field values
    const SCOPE_GLOBAL = 1;
    const SCOPE_ADMIN = 2;
    const SCOPE_PUBLISHER = 3;
    const SCOPE_USER = 4;

    const SCOPE_TEXT = [
        self::SCOPE_GLOBAL => 'global',
        self::SCOPE_ADMIN => 'admin',
        self::SCOPE_PUBLISHER => 'publisher',
        self::SCOPE_USER => 'user',
    ];

    // Type field values
    const TYPE_CUSTOM_NOTIFICATION = 'CustomNotification';
    const TYPE_NEW_VIDEO_PUBLISHED = 'NewVideoPublished';
    const TYPE_DELETE_VIDEO = 'DeleteVideo';
    const TYPE_HIDE_VIDEO = 'HideVideo';
    const TYPE_UNHIDE_VIDEO = 'UnHideVideo';
    const TYPE_FILL_CUSTOM_FEED_TAGS = 'FillCustomFeedTags';
    const TYPE_NEW_IMPORT_REQUEST = 'NewImportRequest';
    const TYPE_IMPORT_REQUEST_ACCEPTED = 'ImportRequestAccepted';
    const TYPE_IMPORT_REQUEST_COMPLETED = 'ImportRequestCompleted';
    const TYPE_NEW_MESSAGE = 'NewMessage';
    const TYPE_REPLY_MESSAGE = 'ReplyMessage';
    const TYPE_NEW_PUBLISHER_REQUEST = 'NewPublisherRequest';
    const TYPE_PUBLISHER_APPROVED = 'PublisherApproved';
    const TYPE_PUBLISHER_REJECTED = 'PublisherRejected';
    const TYPE_REPORT_COMMENT = 'ReportComment';
    const TYPE_REPORT_VIDEO = 'ReportVideo';
    const TYPE_UPDATE_CHANNEL_STATUS = 'UpdateChannelStatus';
    const TYPE_MENTIONED_ON_COMMENT = 'MentionedOnComment';

    protected static function booted()
    {
        static::addGlobalScope(new WherePublishedScope());
    }

    public function users(){
        return $this->belongsToMany('App\Models\User')->withPivot(["read_at"]);
    }

    public function from(){
        return $this->belongsTo('App\Models\User', 'sender_id');
    }

    public function DeletedBy(){
        return $this->belongsTo('App\Models\User', 'deleted_by')->withTrashed();
    }

    public function entity(){
        return $this->morphTo();
    }

    // Attributes and Mutators
    public function getScopeTextAttribute(){
        return self::SCOPE_TEXT[$this->scope]?? $this->scope;
    }

    public function getUserGroupTextAttribute(){
        return self::USER_GROUP_TEXT[$this->user_group]?? $this->user_group;
    }
}

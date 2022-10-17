<?php

namespace App\Models;

use Amir\Permission\Models\Role;
use Amir\Permission\Traits\HasRoles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, Billable;


    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 2;

    // roles
    const ADMIN_ROLE = 'admin';
    const PUBLISHER_ROLE = 'publisher';

    // mute durations
    const MUTE_1WEEK = 604800;
    const MUTE_2WEEK = 1209600;
    const MUTE_1MONTH = 2592000;
    const MUTE_3MONTH = 7776000;
    const MUTE_6MONTH = 15552000;
    const MUTE_1YEAR = 31104000;
    const MUTE_PERMANENT = 0;

    const MUTED_UNTIL_TEXT = [
        self::MUTE_1WEEK => '1_week',
        self::MUTE_2WEEK => '2_week',
        self::MUTE_1MONTH => '1_month',
        self::MUTE_3MONTH => '3_month',
        self::MUTE_6MONTH => '6_month',
        self::MUTE_1YEAR => '1_year',
        self::MUTE_PERMANENT => 'permanent',
    ];

    const PUNCTUATION_MARKS = [' ', '!', '.', '-', '_', ','];

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
        'eth_address',
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

    public function scopeIsHero($query){
        $query->where('hero_due_at', '>', now());
        return $query;
    }

    public function scopeIsNonHero($query){
        $query->where(function ($query) {
            $query->whereNull('hero_due_at')
                ->orWhere('hero_due_at', '<=', now());
        });
        return $query;
    }

    // roles scopes

    public function scopePublishers($query){
        $publisherRoleId = Role::firstOrCreate(['name' => self::PUBLISHER_ROLE])->id;
        $query->where('role_id', $publisherRoleId);
        return $query;
    }

    public function scopeNotPublishers($query){
        $query->where(function ($q){
            $publisherRoleId = Role::firstOrCreate(['name' => self::PUBLISHER_ROLE])->id;
            $q->whereNull('role_id')->orWhere('role_id', "<>", $publisherRoleId);
        });
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

    public function referrer(){
        return $this->belongsTo('App\Models\User', 'referrer_id');
    }

    public function referrals(){
        return $this->hasMany('App\Models\User', 'referrer_id');
    }

    public function meta(){
        return $this->hasMany('App\Models\UserMeta');
    }

    public function paymentDetails(){
        return $this->hasMany('App\Models\PaymentDetails');
    }

    public function verifiedPaymentDetails(){
        return $this->hasOne('App\Models\PaymentDetails')->status(PaymentDetails::STATUS_VERIFIED)->orderBy('last_status_at', 'DESC');
    }

    public function lastPaymentDetails(){
        return $this->hasOne('App\Models\PaymentDetails')->nonArchived()->orderBy('created_at', 'DESC');
    }

    public function notifications(){
        return $this->belongsToMany('App\Models\Notification')->orderBy('notifications.created_at', 'desc')->withPivot(["read_at"]);
    }

    public function unreadNotifications(){
        return $this->notifications()->wherePivotNull('read_at');
    }

    public function channel(){
        return $this->hasOne('App\Models\Channel', 'user_id');
    }

    public function _2fa(){
        return $this->hasOne('App\Models\_2FA', 'user_id');
    }

    public function subscribedChannels(){
        return $this->belongsToMany('App\Models\Channel', 'channel_user', 'user_id')->withTimestamps();
    }

    public function messages(){
        return $this->belongsToMany('App\Models\Message');
    }

    public function comments(){
        return $this->hasMany('App\Models\Comment');
    }

    public function likedVideos(){
        return $this->belongsToMany('App\Models\Video')->withPivot('relation')->where('relation', UserVideo::LIKED_RELATION);
    }

    public function dislikedVideos(){
        return $this->belongsToMany('App\Models\Video')->withPivot('relation')->where('relation', UserVideo::DISLIKED_RELATION);
    }

    public function bookmarkVideos(){
        return $this->belongsToMany('App\Models\Video')->withPivot('relation')->where('relation', UserVideo::BOOKMARKED_RELATION);
    }

    public function watch_times(){
        return $this->belongsToMany('App\Models\Video', "watch_times")->withTimestamps()->withPivot(["start_time","end_time"]);
    }

    public function favoriteCryptoCurrencies(){
        return $this->belongsToMany('App\Models\CryptoCurrency', 'crypto_currency_user');
    }

    public function favoriteTags(){
        return $this->belongsToMany('App\Models\Tag');
    }

    public function pricing(){
        return $this->belongsToMany('App\Models\Pricing')->withTimestamps();
    }

    public function statistics(){
        return $this->setConnection('mongodb')->hasMany('App\Models\UserStatisticsDaily');
    }


    // Attributes
    public function getIsHeroAttribute()
    {
        return $this->hero_due_at > now();
    }

    public function getHasMembershipHistoryAttribute()
    {
        $pricingUserQuery = PricingUser::where('user_id', $this->id);
        return $pricingUserQuery->count() > 0;
    }

    public function getIsMuteAttribute($value)
    {
        return $value && (empty($this->muted_until) || $this->muted_until > now());
    }

    public function getIsAdminAttribute()
    {
        $adminRoleId = Role::firstOrCreate(['name' => self::ADMIN_ROLE])->id;

        return $this->role_id == $adminRoleId;
    }

    public function getAvatarAttribute($value)
    {
        return $this->avatar_url? : $value;
        //return $this->avatar_url? (strpos($this->avatar_url, 'cloudflarestorage') !== false? getR2TemporaryUrl($this->avatar_url) : $this->avatar_url) : $value;
    }

    public function getAvatarThumbnailsAttribute()
    {
        if (!$this->attributes['avatar_url']){
            return [];
        }

        foreach ($urls = getThumbnails($this->attributes['avatar_url']) as $key => $value){
            $urls[$key] = $value;
            //$urls[$key] = strpos($value, 'cloudflarestorage') !== false? getR2TemporaryUrl($value): $value;
        }

        return $urls;
    }

    public function getLikedVideosCountAttribute()
    {
        return DB::table('user_video')->where([
            'relation' => UserVideo::LIKED_RELATION,
            'user_id' => $this->id
        ])->count();
    }

    public function getDislikedVideosCountAttribute()
    {
        return DB::table('user_video')->where([
            'relation' => UserVideo::DISLIKED_RELATION,
            'user_id' => $this->id
        ])->count();
    }

    public function getBookmarkedVideosCountAttribute()
    {
        return DB::table('user_video')->where([
            'relation' => UserVideo::BOOKMARKED_RELATION,
            'user_id' => $this->id
        ])->count();
    }

    public function getCommentsCountAttribute()
    {
        return $this->comments()->count();
    }

    public function getSubscribedChannelsCountAttribute()
    {
        return DB::table('channel_user')->where('user_id', $this->id)->count();
    }

    public function getPublisherRequestDetailsAttribute($value)
    {
        if (is_null($value)){
            $publisherApplicationDepartmentId = Department::firstOrCreate(['name' => 'Publisher Application'])->id;
            $value = $this->attributes['publisher_request_details'] = Message::where([
                    'user_id' => $this->id,
                    'department_id' => $publisherApplicationDepartmentId
                ]
            )->whereNull('parent_id')->orderBy('created_at', 'asc')->first();
        }

        return $value;
    }

    public function getPublisherRequestAttribute($value)
    {
        if (
            is_null($value)
            && !$this->role_id
            && $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->exists()
        ){
            $value['status'] = $this->meta()->where('key', UserMeta::PUBLISHER_REQUEST_STATUS)->first()->value?? '';
            $value['channel_name'] = $this->meta()->where('key', UserMeta::REQUESTED_CHANNEL_NAME)->first()->value?? '';
            $this->attributes['publisher_request'] = $value;
        }

        return $value;
    }

    public function getIsConversionAttribute()
    {
        $publisherRequestDetails = $this->publisher_request_details;
        return ($this->created_at >= Carbon::now()->subHours(24) || ($publisherRequestDetails && $publisherRequestDetails->created_at < $this->created_at->addHours(24)))? false : true;
    }

    public function getIsPublisherAttribute()
    {
        if (!is_null($this->role_id)){
            $publisherRoleId = Role::firstOrCreate(['name' => self::PUBLISHER_ROLE])->id;

            return $publisherRoleId == $this->role_id;
        }
        
        return false;
    }

    public function getLoyaltyPointsAttribute()
    {
        return floatval($this->statistics()->sum('points'));
    }

    public function getIdenfyNameDataAttribute(){
        $meta = $this->meta()->where('key', UserMeta::IDENTIFICATION_DETAILS)->first();

        return $meta ? [
            'name' => $meta->value['data']['docFirstName'],
            'last_name' => $meta->value['data']['docLastName']
        ] : null;
    }


    // Mutators
    public function setAvatarUrlAttribute($value)
    {
        $this->attributes['avatar_url'] = explode('?', $value)[0];
    }
}
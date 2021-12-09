<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth routes
Route::post('register', '\App\Http\Controllers\Auth\RegisterController@register');
Route::post('login/{scope?}', '\App\Http\Controllers\Auth\LoginController@login')->where('scope', 'admin|publisher');
Route::post('password/send', '\App\Http\Controllers\Auth\LoginController@send_password_reset_link');
Route::get('password/verify/{token}', '\App\Http\Controllers\Auth\LoginController@verify_password_reset_token');
Route::put('password/reset', '\App\Http\Controllers\Auth\LoginController@reset_password');
Route::get('users/verify/{token}', '\App\Http\Controllers\Auth\RegisterController@verify')->name("users.verification.verify");
Route::post('users/resend', '\App\Http\Controllers\Auth\RegisterController@resend')->name("users.verification.resend");

// -- publisher auth routes
Route::post('publisher/register', '\App\Http\Controllers\PublisherController@register');

Route::middleware('auth:api')->get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

// search
Route::get('search/{keyword}', '\App\Http\Controllers\SearchController@index');

// categories
Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->only(['index','show']);

// tags
Route::apiResource('tags', \App\Http\Controllers\TagController::class)->only(['index']);

// channels
Route::get('top-channels', '\App\Http\Controllers\ChannelController@topChannels');


// reports
Route::middleware('auth:api')->post('videos/{id}/report', '\App\Http\Controllers\ReportController@store');
Route::middleware('auth:api')->post('comments/{id}/report', '\App\Http\Controllers\ReportController@store');


// notifications
Route::middleware('auth:api')->get('notifications', '\App\Http\Controllers\NotificationController@index');
Route::middleware('auth:api')->get('notifications/{scope}/count', '\App\Http\Controllers\NotificationController@unReadNotificationsCount')
    ->where('scope', 'admin|publisher|user');
Route::middleware('auth:api')->put('notifications/{scope}/read', '\App\Http\Controllers\NotificationController@allMarkASRead')
    ->where('scope', 'admin|publisher|user');
Route::middleware('auth:api')->put('notifications/{id}/read', '\App\Http\Controllers\NotificationController@markASRead');


// Video API routes
Route::middleware('auth:api')->get('videos/bookmarks', '\App\Http\Controllers\VideoController@bookmarks')->name("videos.bookmarks");
Route::middleware('auth:api')->post('videos/{idOrUrlHash}/watch', '\App\Http\Controllers\VideoController@watch_time_store');
Route::put('videos/{video}/increase_view', '\App\Http\Controllers\VideoController@increase_view');
Route::get('videos/{ir_or_url_hash}', '\App\Http\Controllers\VideoController@show');
Route::get('videos', '\App\Http\Controllers\VideoController@index');
Route::get('videos/{video}/related', '\App\Http\Controllers\VideoController@related_videos');

// Video End Screen Cards
Route::get('videos/{id_or_url_hash}/layers', '\App\Http\Controllers\VideoMetaController@getLayers');

// Video chapters
Route::get('videos/{id_or_url_hash}/chapters', '\App\Http\Controllers\ChapterController@index');
Route::get('videos/{id_or_url_hash}/subtitles', '\App\Http\Controllers\SubtitleController@getSubtitles');


// Video like/dislike routes
Route::middleware('auth:api')->put('videos/{video}/like', '\App\Http\Controllers\UserVideoRelationController@like');
Route::middleware('auth:api')->put('videos/{video}/dislike', '\App\Http\Controllers\UserVideoRelationController@dislike');

// Bookmark a video
Route::middleware('auth:api')->put('videos/{video}/bookmark', '\App\Http\Controllers\UserVideoRelationController@bookmark');


// Comments API
Route::get('comments/{comment}', '\App\Http\Controllers\CommentController@show');
Route::get('videos/{idOrHash}/comments', '\App\Http\Controllers\VideoController@comments');



// Crypto Currencies API
Route::get('cryptocurrencies', '\App\Http\Controllers\CryptoCurrencyController@index');
Route::middleware('auth:api')->get('cryptocurrencies/favorites', '\App\Http\Controllers\CryptoCurrencyController@favorites');
Route::middleware('auth:api')->put('cryptocurrencies/{cryptocurrency}/add-to-fav', '\App\Http\Controllers\CryptoCurrencyController@addToFavorites');
Route::middleware('auth:api')->put('cryptocurrencies/{cryptocurrency}/remove-from-fav', '\App\Http\Controllers\CryptoCurrencyController@removeFromFavorites');


// -- add a comment to a video
Route::middleware('auth:api')->post('videos/{idOrHash}/comments', '\App\Http\Controllers\VideoController@storeComment');
// -- reply to a comment
Route::middleware('auth:api')->post('comments/{comment}/reply', '\App\Http\Controllers\CommentController@reply');
// -- like/dislike a comment
Route::middleware('auth:api')->put('comments/{comment}/like', '\App\Http\Controllers\CommentUserRelationController@like');
Route::middleware('auth:api')->put('comments/{comment}/dislike', '\App\Http\Controllers\CommentUserRelationController@dislike');
// -- pin/unpin a comment
Route::middleware('auth:api')->put('comments/{comment}/pin', '\App\Http\Controllers\CommentController@pin');
Route::middleware('auth:api')->put('comments/{comment}/unpin', '\App\Http\Controllers\CommentController@unpin');


// Playlist API
Route::get('channels/{idOrHash}/playlists', '\App\Http\Controllers\PlaylistController@index');
Route::get('my-playlists', '\App\Http\Controllers\PlaylistController@index');
Route::middleware('auth:api')->apiResource('playlists', \App\Http\Controllers\PlaylistController::class)->except(['index','show']);
Route::get('playlists/{idOrHash}', '\App\Http\Controllers\PlaylistController@show');

// -- add video to playlist
Route::middleware('auth:api')->put('playlists/{playlist}/add/{video}', '\App\Http\Controllers\PlaylistController@add');
// -- remove video from playlist
Route::middleware('auth:api')->put('playlists/{playlist}/remove/{video}', '\App\Http\Controllers\PlaylistController@remove');

// -- bulk add video to playlist
Route::middleware('auth:api')->put('playlist/add', '\App\Http\Controllers\PlaylistController@bulkAdd');
// -- bulk remove video from playlist
Route::middleware('auth:api')->put('playlist/remove', '\App\Http\Controllers\PlaylistController@bulkRemove');


// Channel API
Route::get('channels/{id_or_slug}', '\App\Http\Controllers\ChannelController@show');
Route::apiResource('channels', \App\Http\Controllers\ChannelController::class)->only(['index']);
Route::middleware('auth:api')->put('channels/{channel}/subscription', '\App\Http\Controllers\ChannelController@subscription');


// User controller
// -- get profile
Route::middleware('auth:api')->get('profile', '\App\Http\Controllers\UserController@profile');
Route::middleware('auth:api')->post('profile', '\App\Http\Controllers\UserController@updateProfile');
Route::middleware('auth:api')->get('subscribed-channels', '\App\Http\Controllers\UserController@subscribedChannels');


// -- upload
Route::middleware('auth:api')->post('upload', '\App\Http\Controllers\UploadController@upload');


// messages
Route::middleware('auth:api')->apiResource('messages', \App\Http\Controllers\MessageController::class)->except(["update","destroy"]);
Route::middleware('auth:api')->post('messages/{reply_to}/reply', '\App\Http\Controllers\MessageController@store')->name("messages.reply");
Route::middleware('auth:api')->put('messages/{message}/seen', '\App\Http\Controllers\MessageController@update')->name("messages.seen");
Route::middleware('auth:api')->put('messages/{message}/close', '\App\Http\Controllers\MessageController@update')->name("messages.close");


// options
Route::get('options/reasons/{key}', '\App\Http\Controllers\OptionController@getReasonsOption')->name("options.reasons.get");

// lotteries
Route::get('lotteries', '\App\Http\Controllers\LotteryController@index')->name('lotteries.index');


// Departments
Route::get('departments', '\App\Http\Controllers\DepartmentController@index')->name("departments");

// Languages
Route::apiResource('languages', \App\Http\Controllers\LanguageController::class)->only(['index']);


// Become A Publisher
Route::middleware('auth:api')->post('publisher/apply', '\App\Http\Controllers\MessageController@becomeAPublisher')->name('.publisher.apply');

// hero membership
Route::apiResource('plans', '\App\Http\Controllers\PlanController')->only(['index']);
Route::apiResource('payment-methods', '\App\Http\Controllers\PaymentMethodController')->only(['index']);

// Points
Route::get('points/rate', '\App\Http\Controllers\PointController@pointToUsdRate');

// CoinBase
Route::post('coinbase/webhook-handler', '\App\Http\Controllers\CoinbaseController@webHookHandler');


// Stripe
Route::middleware('auth:api')->get('stripe/setup-intent', '\App\Http\Controllers\StripeController@setupIntent');


// New ETH Address Confirmation
Route::middleware('auth:api')->post('profile/eth-address', '\App\Http\Controllers\UserController@changeETHAddress')->name('change-eth-address');
Route::get('confirm-eth-address/{token}', '\App\Http\Controllers\UserController@changeETHAddressConfirmation')->name('confirm-eth-address');

// Login user roles
Route::group(['middleware' => 'auth:api'], function(){
    Route::post('pricing/{pricing}', '\App\Http\Controllers\HeroMembershipController@store')->name('pricing.store');
    Route::post('pricing/{pricing}/process', '\App\Http\Controllers\HeroMembershipController@processPayment')->name('pricing.processPayment');
    Route::get('profile/pricing', '\App\Http\Controllers\HeroMembershipController@index')->name('profile.pricing');

    Route::get('channel/performance/total', '\App\Http\Controllers\ChannelController@performanceTotal')->name('channel.performance');
    Route::get('channel/performance/monthly', '\App\Http\Controllers\ChannelController@performanceMonthly')->name('channel.monthly-performance');
});


// Publisher api routes
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::get('s3/pre-signed-url-for-upload-video', '\App\Http\Controllers\S3Controller@getPreSignedURLForUploadVideo')->name('videos.s3.upload.pre_signed_url');

    // channels
    Route::get('channel', '\App\Http\Controllers\ChannelController@show')->name('channel.show');
    Route::put('channel', '\App\Http\Controllers\ChannelController@update')->name('channel.update');
    Route::get('channel/statistics/daily', '\App\Http\Controllers\ChannelStatisticsController@daily')->name('channel.statistics.daily');
    Route::get('channel/statistics/monthly', '\App\Http\Controllers\ChannelStatisticsController@monthly')->name('channel.statistics.monthly');
    Route::get('channel/statistics/total', '\App\Http\Controllers\ChannelStatisticsController@total')->name('channel.statistics.overview');

    // videos
    Route::delete('videos', '\App\Http\Controllers\VideoController@bulkDestroy')->name('videos.bulkDestroy');
    Route::post('videos/bulk-pin', '\App\Http\Controllers\VideoController@bulkPinMessage')->name('videos.bulkPin');
    Route::apiResource('videos', \App\Http\Controllers\VideoController::class);

    Route::post('channels/request-import', '\App\Http\Controllers\MessageController@channelImportRequest')->name("channels.request-import");

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('.score-board');

    Route::get('notifications', '\App\Http\Controllers\NotificationController@index')->name('notifications');

    Route::apiResource('videos.chapters', '\App\Http\Controllers\ChapterController')->except(['show','index']);

    Route::post('videos/{id_or_url_hash}/layers', '\App\Http\Controllers\VideoMetaController@setLayers')->name('videos.layers.store');

    Route::post('videos/{id_or_url_hash}/subtitles', '\App\Http\Controllers\SubtitleController@store')->name('videos.subtitles.store');
    Route::delete('subtitles/{subtitle}', '\App\Http\Controllers\SubtitleController@destroy')->name('videos.subtitles.destroy');

    Route::apiResource('comments', \App\Http\Controllers\CommentController::class)->only(['index']);

    Route::get('videos/{id_or_url_hash}/statistics/daily', '\App\Http\Controllers\VideoStatisticsController@daily')->name('video.statistics.daily');
    Route::get('videos/{id_or_url_hash}/statistics/monthly', '\App\Http\Controllers\VideoStatisticsController@monthly')->name('video.statistics.monthly');
    Route::get('videos/{id_or_url_hash}/statistics/total', '\App\Http\Controllers\VideoStatisticsController@total')->name('video.statistics.overview');

    Route::apiResource('earnings', '\App\Http\Controllers\EarningController')->only(['index']);
    Route::get('earnings/total', '\App\Http\Controllers\EarningController@total')->name('earnings.report-total');
    Route::get('earnings/monthly', '\App\Http\Controllers\EarningController@monthly')->name('earnings.report-monthly');

});


// Admin api routes
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){
    Route::get('dashboard', '\App\Http\Controllers\GeneralController@adminDashboard')->name('dashboard');
    Route::get('channels/statistics/daily', '\App\Http\Controllers\ChannelStatisticsController@daily')->name('channels.statistics.daily');
    Route::get('channels/statistics/monthly', '\App\Http\Controllers\ChannelStatisticsController@monthly')->name('channels.statistics.monthly');
    Route::get('channels/statistics/total', '\App\Http\Controllers\ChannelStatisticsController@total')->name('channels.statistics.total');

    Route::apiResource('comments', \App\Http\Controllers\CommentController::class)->only(['index','destroy']);

    Route::apiResource('categories', \App\Http\Controllers\VideoController::class)->only(['store', 'update', 'destroy']);

    Route::apiResource('roles', \App\Http\Controllers\RoleController::class)->only(['index']);

    Route::get('users', '\App\Http\Controllers\UserController@index')->name('users');
    Route::get('users/{user}', '\App\Http\Controllers\UserController@show')->name('users.show');
    Route::post('users', '\App\Http\Controllers\UserController@store')->name('users.store');
    Route::put('users/{user}', '\App\Http\Controllers\UserController@update')->name('users.update');
    Route::delete('users/{user}', '\App\Http\Controllers\UserController@destroy')->name('users.destroy');

    Route::get('users/{user}/performance/total', '\App\Http\Controllers\ChannelController@performanceTotal')->name('channels.performance');
    Route::get('users/{user}/performance/monthly', '\App\Http\Controllers\ChannelController@performanceMonthly')->name('channels.monthly-performance');

    Route::get('publishers', '\App\Http\Controllers\UserController@index')->name('publishers');
    Route::get('publishers/{user}', '\App\Http\Controllers\UserController@show')->name('publishers.show');

    Route::get('admins', '\App\Http\Controllers\UserController@index')->name('admins');
    Route::post('admins', '\App\Http\Controllers\UserController@store')->name('admins.store');

    Route::get('publisher-requests', '\App\Http\Controllers\UserController@index')->name('publisher_requests');
    Route::put('publisher-requests/{user}/confirm', '\App\Http\Controllers\PublisherController@confirm')->name('publisher_requests.confirm');
    Route::put('publisher-requests/{user}/reject', '\App\Http\Controllers\PublisherController@reject')->name('publisher_requests.reject');

    Route::get('videos', '\App\Http\Controllers\VideoController@index')->name('videos');
    Route::post('videos', '\App\Http\Controllers\VideoController@store')->name("videos.store");

    Route::get('videos/{video}', '\App\Http\Controllers\VideoController@show')->name('videos.delete');
    Route::delete('videos/{video}', '\App\Http\Controllers\VideoController@destroy')->name('videos.delete');

    Route::get('videos/{id_or_url_hash}/layers', '\App\Http\Controllers\VideoMetaController@getLayers')->name('videos.layers.index');
    Route::get('videos/{id_or_url_hash}/subtitles', '\App\Http\Controllers\SubtitleController@getSubtitles')->name('videos.subtitles.index');


    Route::get('videos/{id_or_url_hash}/statistics/daily', '\App\Http\Controllers\VideoStatisticsController@daily')->name('video.statistics.daily');
    Route::get('videos/{id_or_url_hash}/statistics/monthly', '\App\Http\Controllers\VideoStatisticsController@monthly')->name('video.statistics.monthly');
    Route::get('videos/{id_or_url_hash}/statistics/total', '\App\Http\Controllers\VideoStatisticsController@total')->name('video.statistics.overview');

    Route::put('videos/{video}/hide', '\App\Http\Controllers\VideoController@hide')->name('videos.hide');
    Route::put('videos/{video}/unhide', '\App\Http\Controllers\VideoController@unHide')->name('videos.unhide');


    Route::get('channels/import-requests', '\App\Http\Controllers\ChannelController@importRequests')->name("channels.import_requests");
    Route::post('channels/{channel}/import-completed', '\App\Http\Controllers\ChannelController@importCompleted')->name("channels.import_completed");
    Route::put('channels/{channel}/import-request', '\App\Http\Controllers\ChannelController@importRequest')->name("channels.import_request");
    Route::apiResource('channels', \App\Http\Controllers\ChannelController::class);
    Route::get('channels/{channel}/statistics/daily', '\App\Http\Controllers\ChannelStatisticsController@daily')->name('channel.statistics.daily');
    Route::get('channels/{channel}/statistics/monthly', '\App\Http\Controllers\ChannelStatisticsController@monthly')->name('channel.statistics.monthly');
    Route::get('channels/{channel}/statistics/total', '\App\Http\Controllers\ChannelStatisticsController@total')->name('channel.statistics.overview');


    Route::post('playlists', '\App\Http\Controllers\PlaylistController@store')->name("playlists.store");
    Route::get('channels/{idOrHash}/playlists', '\App\Http\Controllers\PlaylistController@index')->name('users.playlists.index');

    Route::apiResource('messages', \App\Http\Controllers\MessageController::class)->except("update");
    Route::post('messages/{reply_to}/reply', '\App\Http\Controllers\MessageController@store')->name("messages.reply");
    Route::put('messages/{message}/seen', '\App\Http\Controllers\MessageController@update')->name("messages.seen");
    Route::put('messages/{message}/close', '\App\Http\Controllers\MessageController@update')->name("messages.close");

    Route::delete('comments/{comment}', '\App\Http\Controllers\CommentController@destroy')->name('comments.destroy');

    Route::get('reports/video', '\App\Http\Controllers\ReportController@index');
    Route::get('reports/comment', '\App\Http\Controllers\ReportController@index');
    Route::get('reports/video/{id}', '\App\Http\Controllers\ReportController@index_reports')->name("video.reports");
    Route::get('reports/comment/{id}', '\App\Http\Controllers\ReportController@index_reports')->name("comment.reports");

    Route::post('options/reasons/{key}', '\App\Http\Controllers\OptionController@setReasonsOption')->name("options.reasons.store");

    Route::get('notifications', '\App\Http\Controllers\NotificationController@index')->name('notifications');
    Route::get('notifications/sent-by-admin', '\App\Http\Controllers\NotificationController@index_sent_by_admin')->name('notifications.sent_by_admin');
    Route::post('notifications/{scope}', '\App\Http\Controllers\NotificationController@store')
        ->where('scope', 'publisher|user')->name('notifications.store');


    Route::apiResource('payment-methods', '\App\Http\Controllers\PaymentMethodController')->only(['index']);
    Route::apiResource('plans', '\App\Http\Controllers\PlanController');

    Route::get('memberships', '\App\Http\Controllers\HeroMembershipController@index')->name('memberships.index');
    Route::get('membership/earnings/daily', '\App\Http\Controllers\HeroMembershipController@earningsDaily')->name('membership.earnings.report-daily');
    Route::get('membership/earnings/monthly', '\App\Http\Controllers\HeroMembershipController@earningsMonthly')->name('membership.earnings.report-monthly');
    Route::get('membership/earnings/total', '\App\Http\Controllers\HeroMembershipController@earningsTotal')->name('membership.earnings.report-total');

    Route::apiResource('transactions', '\App\Http\Controllers\TransactionController')->only(['index']);

    Route::apiResource('earnings', '\App\Http\Controllers\EarningController')->only(['index']);
    Route::get('earnings/total', '\App\Http\Controllers\EarningController@total')->name('earnings.report-total');
    Route::get('earnings/monthly', '\App\Http\Controllers\EarningController@monthly')->name('earnings.report-monthly');
    Route::post('earnings/calc', '\App\Http\Controllers\EarningController@calcEarnings')->name('earnings.calc');
    Route::put('earnings/{earning}/paid', '\App\Http\Controllers\EarningController@setToPaid')->name('earnings.paid');

    // Exports
    Route::get('users/publishers-earnings/export', '\App\Http\Controllers\ChannelController@exportPublishersEarnings')->name('users.publishers-earnings.export');

    // Lottery
    Route::get('lotteries', '\App\Http\Controllers\LotteryController@index')->name('lotteries.index');
    Route::post('lotteries', '\App\Http\Controllers\LotteryController@lottery')->name('lotteries.lottery');
    Route::put('lotteries/{lottery_user_id}/paid', '\App\Http\Controllers\LotteryController@setToPaid')->name('lotteries.paid');

    // tags
    Route::apiResource('tags', \App\Http\Controllers\TagController::class);
});


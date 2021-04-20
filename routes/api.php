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
Route::post('login', '\App\Http\Controllers\Auth\LoginController@login');

// -- publisher auth routes
Route::post('publisher/register', '\App\Http\Controllers\PublisherController@register');

Route::middleware('auth:api')->get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);

// Video API routes
Route::get('videos/{ir_or_url_hash}', '\App\Http\Controllers\VideoController@show');
Route::middleware('auth:api')->apiResource('videos', \App\Http\Controllers\VideoController::class);
Route::get('videos', '\App\Http\Controllers\VideoController@index');
Route::delete('videos', '\App\Http\Controllers\VideoController@bulkDestroy');
Route::post('videos/bulk-pin', '\App\Http\Controllers\VideoController@bulkPinMessage');

// Video like/dislike routes
Route::middleware('auth:api')->put('videos/{video}/like', '\App\Http\Controllers\UserVideoRelationController@like');
Route::middleware('auth:api')->put('videos/{video}/dislike', '\App\Http\Controllers\UserVideoRelationController@dislike');

// Bookmark a video
Route::middleware('auth:api')->put('videos/{video}/bookmark', '\App\Http\Controllers\UserVideoRelationController@bookmark');


// Comments API
Route::middleware('auth:api')->apiResource('comments', \App\Http\Controllers\CommentController::class);

Route::get('comments/{comment}', '\App\Http\Controllers\CommentController@show');


// -- add a comment to a video
Route::middleware('auth:api')->post('videos/{video}/comments', '\App\Http\Controllers\VideoController@comment');
// -- reply to a comment
Route::middleware('auth:api')->post('comments/{comment}/reply', '\App\Http\Controllers\CommentController@reply');
// -- like/dislike a comment
Route::middleware('auth:api')->put('comments/{comment}/like', '\App\Http\Controllers\CommentUserRelationController@like');
Route::middleware('auth:api')->put('comments/{comment}/dislike', '\App\Http\Controllers\CommentUserRelationController@dislike');
// -- pin/unpin a comment
Route::middleware('auth:api')->put('comments/{comment}/pin', '\App\Http\Controllers\CommentController@pin');
Route::middleware('auth:api')->put('comments/{comment}/unpin', '\App\Http\Controllers\CommentController@unpin');



// Playlist API
Route::middleware('auth:api')->apiResource('playlists', \App\Http\Controllers\PlaylistController::class);

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
Route::middleware('auth:api')->apiResource('channels', \App\Http\Controllers\ChannelController::class);
Route::middleware('auth:api')->get('channel', '\App\Http\Controllers\ChannelController@show');
Route::middleware('auth:api')->put('channel', '\App\Http\Controllers\ChannelController@update');
Route::get('channels', '\App\Http\Controllers\ChannelController@index');
Route::middleware('auth:api')->put('channels/{channel}/subscription', '\App\Http\Controllers\ChannelController@subscription');


// -- add video to a channel
Route::middleware('auth:api')->put('channels/{channel}/add/{video}', '\App\Http\Controllers\ChannelController@add');
// -- remove video from channels
Route::middleware('auth:api')->put('channels/{channel}/remove/{video}', '\App\Http\Controllers\ChannelController@remove');


// User controller
// -- get profile
Route::middleware('auth:api')->get('profile', '\App\Http\Controllers\UserController@profile');
Route::middleware('auth:api')->post('profile', '\App\Http\Controllers\UserController@updateProfile');


// Utils

// -- upload
Route::middleware('auth:api')->post('upload', '\App\Http\Controllers\UploadController@upload');


// Publisher api routes
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){
    Route::get('videos', '\App\Http\Controllers\VideoController@index')->name('.videos');
    Route::post('apply', '\App\Http\Controllers\MessageController@becomeAPublisher')->name('.messages');

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('.score-board');
});


// Admin api routes
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){
    Route::get('users', '\App\Http\Controllers\UserController@index')->name('users');
    Route::get('users/{user}', '\App\Http\Controllers\UserController@show')->name('users.show');
    Route::post('users/{user}', '\App\Http\Controllers\UserController@update')->name('users.update');

    Route::get('publishers', '\App\Http\Controllers\UserController@index')->name('publishers');
    Route::get('publishers/{user}', '\App\Http\Controllers\UserController@show')->name('publishers.show');
    Route::get('users/{user}', '\App\Http\Controllers\UserController@show')->name('users.show');
    Route::get('admins', '\App\Http\Controllers\UserController@index')->name('admins');

    Route::get('publisher-requests', '\App\Http\Controllers\UserController@index')->name('publisher_requests');
    Route::put('publisher-requests/{user}/confirm', '\App\Http\Controllers\PublisherController@confirm')->name('publisher_requests.confirm');
    Route::put('publisher-requests/{user}/reject', '\App\Http\Controllers\PublisherController@reject')->name('publisher_requests.reject');

    Route::get('videos', '\App\Http\Controllers\VideoController@index')->name('videos');

    Route::delete('videos/{video}', '\App\Http\Controllers\VideoController@destroy')->name('videos.delete');

    Route::put('videos/{video}/hide', '\App\Http\Controllers\VideoController@hide')->name('videos.hide');

    Route::apiResource('channels', \App\Http\Controllers\ChannelController::class);

    Route::apiResource('messages', \App\Http\Controllers\MessageController::class);

});
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
Route::middleware('auth:api')->get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);

// Video API routes
Route::get('videos/{ir_or_url_hash}', '\App\Http\Controllers\VideoController@show');
Route::middleware('auth:api')->apiResource('videos', \App\Http\Controllers\VideoController::class);
Route::get('videos', '\App\Http\Controllers\VideoController@index');

// Video like/dislike routes
Route::middleware('auth:api')->get('videos/{video}/like', '\App\Http\Controllers\UserVideoRelationController@like');
Route::middleware('auth:api')->get('videos/{video}/dislike', '\App\Http\Controllers\UserVideoRelationController@dislike');

// Comments API
Route::middleware('auth:api')->apiResource('comments', \App\Http\Controllers\CommentController::class);

// -- add a comment to a video
Route::middleware('auth:api')->post('videos/{video}/comments', '\App\Http\Controllers\VideoController@comment');
// -- reply to a comment
Route::middleware('auth:api')->post('comments/{comment}/reply', '\App\Http\Controllers\CommentController@reply');
// -- like/dislike a comment
Route::middleware('auth:api')->get('comments/{comment}/like', '\App\Http\Controllers\CommentUserRelationController@like');
Route::middleware('auth:api')->get('comments/{comment}/dislike', '\App\Http\Controllers\CommentUserRelationController@dislike');


// Playlist API
Route::middleware('auth:api')->apiResource('playlists', \App\Http\Controllers\PlaylistController::class);

// -- add video to playlist
Route::middleware('auth:api')->put('playlists/{playlist}/add/{video}', '\App\Http\Controllers\PlaylistController@add');
// -- remove video from playlist
Route::middleware('auth:api')->put('playlists/{playlist}/remove/{video}', '\App\Http\Controllers\PlaylistController@remove');


// Channel API
Route::middleware('auth:api')->apiResource('channels', \App\Http\Controllers\ChannelController::class);
Route::middleware('auth:api')->get('channel/{channel?}', '\App\Http\Controllers\ChannelController@show');
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


// Utils

// -- upload
Route::middleware('auth:api')->post('upload', '\App\Http\Controllers\UploadController@upload');


// Publisher api routes
Route::group([
    'middleware' => 'auth:api',
    'as' => '.publisher',
    'prefix' => 'publisher'
], function(){
    Route::get('videos', '\App\Http\Controllers\VideoController@index');
});
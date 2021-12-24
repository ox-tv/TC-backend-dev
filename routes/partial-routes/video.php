<?php

use Illuminate\Support\Facades\Route;


Route::put('videos/{video}/increase_view', '\App\Http\Controllers\VideoController@increase_view');
Route::get('videos/{ir_or_url_hash}', '\App\Http\Controllers\VideoController@show');
Route::get('videos', '\App\Http\Controllers\VideoController@index');
Route::get('videos/{video}/related', '\App\Http\Controllers\VideoController@related_videos');

// Video End Screen Cards
Route::get('videos/{id_or_url_hash}/layers', '\App\Http\Controllers\VideoMetaController@getLayers');

// Video chapters
Route::get('videos/{id_or_url_hash}/chapters', '\App\Http\Controllers\ChapterController@index');
Route::get('videos/{id_or_url_hash}/subtitles', '\App\Http\Controllers\SubtitleController@getSubtitles');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('videos/{idOrUrlHash}/watch', '\App\Http\Controllers\VideoController@watch_time_store');

    // Video like/dislike routes
    Route::put('videos/{video}/like', '\App\Http\Controllers\UserVideoRelationController@like')->middleware('user.unmute');
    Route::put('videos/{video}/dislike', '\App\Http\Controllers\UserVideoRelationController@dislike')->middleware('user.unmute');

    // Bookmark a video
    Route::get('videos/bookmarks', '\App\Http\Controllers\VideoController@bookmarks')->name("videos.bookmarks");
    Route::put('videos/{video}/bookmark', '\App\Http\Controllers\UserVideoRelationController@bookmark');

    // -- add video to playlist
    Route::put('playlists/{playlist}/add/{video}', '\App\Http\Controllers\PlaylistController@add');
    // -- remove video from playlist
    Route::put('playlists/{playlist}/remove/{video}', '\App\Http\Controllers\PlaylistController@remove');

    // -- bulk add video to playlist
    Route::put('playlist/add', '\App\Http\Controllers\PlaylistController@bulkAdd');
    // -- bulk remove video from playlist
    Route::put('playlist/remove', '\App\Http\Controllers\PlaylistController@bulkRemove');

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::delete('videos', '\App\Http\Controllers\VideoController@bulkDestroy')->name('videos.bulkDestroy');
    Route::post('videos/bulk-pin', '\App\Http\Controllers\VideoController@bulkPinMessage')->name('videos.bulkPin');
    Route::apiResource('videos', \App\Http\Controllers\VideoController::class);

    Route::apiResource('videos.chapters', '\App\Http\Controllers\ChapterController')->except(['show','index']);

    Route::post('videos/{id_or_url_hash}/layers', '\App\Http\Controllers\VideoMetaController@setLayers')->name('videos.layers.store');

    Route::post('videos/{id_or_url_hash}/subtitles', '\App\Http\Controllers\SubtitleController@store')->name('videos.subtitles.store');
    Route::delete('subtitles/{subtitle}', '\App\Http\Controllers\SubtitleController@destroy')->name('videos.subtitles.destroy');

    Route::get('videos/{id_or_url_hash}/statistics/daily', '\App\Http\Controllers\VideoStatisticsController@daily')->name('video.statistics.daily');
    Route::get('videos/{id_or_url_hash}/statistics/monthly', '\App\Http\Controllers\VideoStatisticsController@monthly')->name('video.statistics.monthly');
    Route::get('videos/{id_or_url_hash}/statistics/total', '\App\Http\Controllers\VideoStatisticsController@total')->name('video.statistics.overview');

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('videos', '\App\Http\Controllers\VideoController@index')->name('videos');
    Route::post('videos', '\App\Http\Controllers\VideoController@store')->name("videos.store");

    Route::get('videos/{video}', '\App\Http\Controllers\VideoController@show')->name('videos.show');
    Route::delete('videos/{video}', '\App\Http\Controllers\VideoController@destroy')->name('videos.delete');

    Route::get('videos/{id_or_url_hash}/layers', '\App\Http\Controllers\VideoMetaController@getLayers')->name('videos.layers.index');
    Route::get('videos/{id_or_url_hash}/subtitles', '\App\Http\Controllers\SubtitleController@getSubtitles')->name('videos.subtitles.index');


    Route::get('videos/{id_or_url_hash}/statistics/daily', '\App\Http\Controllers\VideoStatisticsController@daily')->name('video.statistics.daily');
    Route::get('videos/{id_or_url_hash}/statistics/monthly', '\App\Http\Controllers\VideoStatisticsController@monthly')->name('video.statistics.monthly');
    Route::get('videos/{id_or_url_hash}/statistics/total', '\App\Http\Controllers\VideoStatisticsController@total')->name('video.statistics.overview');

    Route::put('videos/{video}/hide', '\App\Http\Controllers\VideoController@hide')->name('videos.hide');
    Route::put('videos/{video}/unhide', '\App\Http\Controllers\VideoController@unHide')->name('videos.unhide');

});
<?php

use Illuminate\Support\Facades\Route;




// For Login Users
Route::group(['middleware' => 'auth:api'], function(){
    Route::put('channels/{channel}/subscription', '\App\Http\Controllers\ChannelController@subscription');

    Route::get('channel/performance/total', '\App\Http\Controllers\ChannelController@performanceTotal')->name('channel.performance');
    Route::get('channel/performance/monthly', '\App\Http\Controllers\ChannelController@performanceMonthly')->name('channel.monthly-performance');

});


//Route::get('top-channels', '\App\Http\Controllers\ChannelController@topChannels');

Route::get('channels/{id_or_slug}', '\App\Http\Controllers\ChannelController@show');
Route::apiResource('channels', \App\Http\Controllers\ChannelController::class)->only(['index']);


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::get('channel', '\App\Http\Controllers\ChannelController@show')->name('channel.show');
    Route::put('channel', '\App\Http\Controllers\ChannelController@update')->name('channel.update')->middleware(['channel.unfreeze']);

    Route::get('channel/statistics/daily', '\App\Http\Controllers\ChannelStatisticsController@daily')->name('channel.statistics.daily');
    Route::get('channel/statistics/monthly', '\App\Http\Controllers\ChannelStatisticsController@monthly')->name('channel.statistics.monthly');
    Route::get('channel/statistics/total', '\App\Http\Controllers\ChannelStatisticsController@total')->name('channel.statistics.overview');

    Route::post('channels/request-import', '\App\Http\Controllers\MessageController@channelImportRequest')
        ->name("channels.request-import")->middleware('channel.unfreeze');

    Route::put('yi/channels/sync-request', '\App\Http\Controllers\YoutubeImporterController@syncRequest')->name("yi.channels.sync-request");
    Route::get('yi/channels/import-stats', '\App\Http\Controllers\YoutubeImporterController@importStats')->name("yi.channels.import-stats");

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('channels/statistics/daily', '\App\Http\Controllers\ChannelStatisticsController@daily')->name('channels.statistics.daily');
    Route::get('channels/statistics/monthly', '\App\Http\Controllers\ChannelStatisticsController@monthly')->name('channels.statistics.monthly');
    Route::get('channels/statistics/total', '\App\Http\Controllers\ChannelStatisticsController@total')->name('channels.statistics.total');

    Route::get('users/{user}/performance/total', '\App\Http\Controllers\ChannelController@performanceTotal')->name('channels.performance');
    Route::get('users/{user}/performance/monthly', '\App\Http\Controllers\ChannelController@performanceMonthly')->name('channels.monthly-performance');

    Route::get('channels/import-requests', '\App\Http\Controllers\YoutubeImporterController@index')->name("channels.import_requests");
    Route::post('channels/{channel}/import-completed', '\App\Http\Controllers\YoutubeImporterController@importCompleted')->name("channels.import_completed");
    Route::put('channels/{channel}/import-request', '\App\Http\Controllers\YoutubeImporterController@importRequest')->name("channels.import_request");

    Route::get('channels/{channel}/statistics/daily', '\App\Http\Controllers\ChannelStatisticsController@daily')->name('channel.statistics.daily');
    Route::get('channels/{channel}/statistics/monthly', '\App\Http\Controllers\ChannelStatisticsController@monthly')->name('channel.statistics.monthly');
    Route::get('channels/{channel}/statistics/total', '\App\Http\Controllers\ChannelStatisticsController@total')->name('channel.statistics.overview');

    Route::put('yi/channels/{channel}', '\App\Http\Controllers\YoutubeImporterController@updateChannel')->name("yi.channels.update");

    Route::apiResource('channels', \App\Http\Controllers\ChannelController::class);

});
<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');
Route::get('home/channels/trending', '\App\Http\Controllers\GeneralController@homeTrendingChannels');
Route::get('home/channels/top', '\App\Http\Controllers\GeneralController@homeTopChannels');
Route::get('home/videos/trending', '\App\Http\Controllers\GeneralController@homeTrendingVideos');
Route::get('home/videos/for-you', '\App\Http\Controllers\GeneralController@homeVideosForYou');

// search
Route::get('search/{keyword}', '\App\Http\Controllers\SearchController@index');

Route::post('inquire', '\App\Http\Controllers\GeneralController@advertisementInquireForm');


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function() {

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('score-board');

    Route::get('dashboard/overview', '\App\Http\Controllers\GeneralController@publisherDashboardOverview')->name('dashboard-overview');
    Route::get('dashboard/charts', '\App\Http\Controllers\GeneralController@publisherDashboardCharts')->name('dashboard-charts');

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('dashboard', '\App\Http\Controllers\GeneralController@adminDashboard')->name('dashboard');

});

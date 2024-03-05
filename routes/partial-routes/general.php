<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

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

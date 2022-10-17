<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

// search
Route::get('search/{keyword}', '\App\Http\Controllers\SearchController@index');


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function() {

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('.score-board');

    Route::get('dashboard', '\App\Http\Controllers\GeneralController@publisherDashboard')->name('dashboard');

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

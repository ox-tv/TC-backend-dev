<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

// search
Route::get('search/{keyword}', '\App\Http\Controllers\SearchController@index');

Route::get('test', function (){
    $playlist = \App\Models\Tag::with([])
        ->first()
        ->append(['favorited_by_users_count', 'videos_count']);
    return  \App\Http\Resources\Tag\TagResource::make($playlist);
});

// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function() {

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('.score-board');

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

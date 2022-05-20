<?php

use Illuminate\Support\Facades\Route;


Route::apiResource('feedback', \App\Http\Controllers\FeedbackController::class)->only(['store']);


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('feedback/locations', '\App\Http\Controllers\FeedbackController@getLocations')->name('feedback.locations');
    Route::apiResource('feedback', \App\Http\Controllers\FeedbackController::class)->only(['index']);

});
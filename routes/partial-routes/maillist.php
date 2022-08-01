<?php

use Illuminate\Support\Facades\Route;


Route::apiResource('mail-list', \App\Http\Controllers\MailListController::class)->only(['store']);


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

    Route::get('mail-list/locations', '\App\Http\Controllers\MailListController@getLocations')->name('mail-list.locations');
    Route::apiResource('mail-list', \App\Http\Controllers\MailListController::class)->only(['index']);

});

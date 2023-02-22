<?php

use Illuminate\Support\Facades\Route;



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

    Route::put('ads/settings', '\App\Http\Controllers\AdController@storeSettings')->name('ads.settings.store');
    Route::get('ads/settings', '\App\Http\Controllers\AdController@getSettings')->name('ads.settings.get');
    //Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->only(['store', 'update', 'destroy']);
});

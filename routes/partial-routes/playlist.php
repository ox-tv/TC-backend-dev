<?php

use Illuminate\Support\Facades\Route;


Route::get('channels/{idOrHash}/playlists', '\App\Http\Controllers\PlaylistController@index');
Route::get('playlists/{idOrHash}', '\App\Http\Controllers\PlaylistController@show');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::apiResource('playlists', \App\Http\Controllers\PlaylistController::class)->except(['index','show']);
    Route::get('my-playlists', '\App\Http\Controllers\PlaylistController@index');

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

    Route::post('playlists', '\App\Http\Controllers\PlaylistController@store')->name("playlists.store");
    Route::get('channels/{idOrHash}/playlists', '\App\Http\Controllers\PlaylistController@index')->name('users.playlists.index');

});
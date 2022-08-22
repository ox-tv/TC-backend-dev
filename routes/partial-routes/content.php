<?php

use Illuminate\Support\Facades\Route;


Route::get('contents/{idOrPage}', '\App\Http\Controllers\ContentController@show')->name('contents.show');


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

    Route::apiResource('contents', \App\Http\Controllers\ContentController::class)->only(['index', 'store']);

});
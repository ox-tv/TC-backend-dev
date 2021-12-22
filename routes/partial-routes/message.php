<?php

use Illuminate\Support\Facades\Route;




// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::apiResource('messages', \App\Http\Controllers\MessageController::class)->except(["update","destroy"]);
    Route::post('messages/{reply_to}/reply', '\App\Http\Controllers\MessageController@store')->name("messages.reply");
    Route::put('messages/{message}/seen', '\App\Http\Controllers\MessageController@update')->name("messages.seen");
    Route::put('messages/{message}/close', '\App\Http\Controllers\MessageController@update')->name("messages.close");

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

    Route::apiResource('messages', \App\Http\Controllers\MessageController::class)->except("update");
    Route::post('messages/{reply_to}/reply', '\App\Http\Controllers\MessageController@store')->name("messages.reply");
    Route::put('messages/{message}/seen', '\App\Http\Controllers\MessageController@update')->name("messages.seen");
    Route::put('messages/{message}/close', '\App\Http\Controllers\MessageController@update')->name("messages.close");

});
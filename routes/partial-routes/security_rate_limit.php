<?php

use Illuminate\Support\Facades\Route;


Route::get('security-rate-limit/report', '\App\Http\Controllers\SecurityRateLimitController@index');
Route::get('security-rate-limit/restore', '\App\Http\Controllers\SecurityRateLimitController@restoreBlockedTokens');

// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    // Email 2FA

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
    Route::get('security-rate-limit/report', '\App\Http\Controllers\SecurityRateLimitController@index');

});
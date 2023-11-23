<?php

use Illuminate\Support\Facades\Route;


Route::get('security-rate-limit/report', '\App\Http\Controllers\SecurityRateLimitController@index');
Route::get('security-rate-limit/restore', '\App\Http\Controllers\SecurityRateLimitController@restoreBlockedTokens');
Route::get('security-rate-limit/disable', '\App\Http\Controllers\SecurityRateLimitController@disableUsers');
Route::get('security-rate-limit/users/info', '\App\Http\Controllers\SecurityRateLimitController@usersInfo');
Route::get('security-rate-limit/user/{id}/referrals', '\App\Http\Controllers\SecurityRateLimitController@userReferrals');
Route::get('security-rate-limit/user/{id}/watch-times', '\App\Http\Controllers\SecurityRateLimitController@userWatchTimes');

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
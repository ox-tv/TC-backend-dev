<?php

use Illuminate\Support\Facades\Route;


Route::put('email/verify', '\App\Http\Controllers\Auth\EmailVerificationController@verify');
Route::put('email/send-code', '\App\Http\Controllers\Auth\EmailVerificationController@sendCode');

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

});
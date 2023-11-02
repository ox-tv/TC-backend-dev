<?php

use Illuminate\Support\Facades\Route;

Route::post('register', '\App\Http\Controllers\Auth\RegisterController@register')->middleware(['throttle:register']);

Route::post('login/magic/{scope?}', '\App\Http\Controllers\Auth\LoginController@sendMagicLogin')->where('scope', 'admin|publisher');
Route::post('login/magic/{token}', '\App\Http\Controllers\Auth\LoginController@verifyMagicLogin');
Route::post('login/{scope?}', '\App\Http\Controllers\Auth\LoginController@login')->where('scope', 'admin|publisher');
Route::post('login-with-wallet/{scope?}', '\App\Http\Controllers\Auth\LoginController@loginWithWallet')->where('scope', 'admin|publisher')->middleware(['throttle:register']);

Route::post('password/send', '\App\Http\Controllers\Auth\LoginController@send_password_reset_link');
Route::get('password/verify/{token}', '\App\Http\Controllers\Auth\LoginController@verify_password_reset_token');
Route::put('password/reset', '\App\Http\Controllers\Auth\LoginController@reset_password');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

});
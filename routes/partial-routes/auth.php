<?php

use Illuminate\Support\Facades\Route;

Route::post('register', '\App\Http\Controllers\Auth\RegisterController@register');
Route::post('publisher/register', '\App\Http\Controllers\PublisherController@register');

Route::post('login/{scope?}', '\App\Http\Controllers\Auth\LoginController@login')->where('scope', 'admin|publisher');

Route::post('password/send', '\App\Http\Controllers\Auth\LoginController@send_password_reset_link');
Route::get('password/verify/{token}', '\App\Http\Controllers\Auth\LoginController@verify_password_reset_token');
Route::put('password/reset', '\App\Http\Controllers\Auth\LoginController@reset_password');

Route::get('users/verify/{token}', '\App\Http\Controllers\Auth\RegisterController@verify')->name("users.verification.verify");
Route::post('users/resend', '\App\Http\Controllers\Auth\RegisterController@resend')->name("users.verification.resend");

Route::group(['middleware' => 'auth:api'], function(){
    Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');
});
<?php

use Illuminate\Support\Facades\Route;


Route::put('2fa/verify', '\App\Http\Controllers\Auth\_2FAController@verify')->name('2fa.verify');

// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    // Email 2FA
    Route::put('2fa/email/send', '\App\Http\Controllers\Auth\_2FAController@sendEmail2FACode')->name('2fa.email.send');
    Route::put('2fa/email/enable', '\App\Http\Controllers\Auth\_2FAController@enableEmail2FA')->name('2fa.email.enable');
    Route::put('2fa/email/disable', '\App\Http\Controllers\Auth\_2FAController@disableEmail2FA')->name('2fa.email.disable')->middleware(['2fa:hard']);

    // Google 2FA
    Route::put('2fa/google/qr-code', '\App\Http\Controllers\Auth\_2FAController@getApp2FAQrCode')->name('2fa.google.qr-code');
    Route::put('2fa/google/enable', '\App\Http\Controllers\Auth\_2FAController@enableGoogle2FA')->name('2fa.google.enable');
    Route::put('2fa/google/disable', '\App\Http\Controllers\Auth\_2FAController@disableGoogle2FA')->name('2fa.google.disable')->middleware(['2fa:hard']);

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
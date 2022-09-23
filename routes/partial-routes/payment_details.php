<?php

use Illuminate\Support\Facades\Route;


// For Logged in Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('profile/payment-details', '\App\Http\Controllers\PaymentDetailsController@store')
        ->name('profile.payment-details.store')/*->middleware(['2fa.or.email-verification'])*/;
    
});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){
    Route::get('profile/address-history', '\App\Http\Controllers\PaymentDetailsController@addressHistory')->name('users.address-history');
});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){
    Route::get('payment-details', '\App\Http\Controllers\PaymentDetailsController@index')->name('payment-details.index');
    Route::get('users/{user}/address-history', '\App\Http\Controllers\PaymentDetailsController@addressHistory')->name('users.address-history');
});
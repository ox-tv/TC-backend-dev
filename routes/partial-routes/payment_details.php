<?php

use Illuminate\Support\Facades\Route;


// For Logged in Users
Route::group(['middleware' => 'auth:api'], function(){
    
});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::post('profile/payment-details/verify', '\App\Http\Controllers\PaymentDetailsController@verifyPaymentDetails')->name('profile.payment-details.verify');
    Route::post('profile/payment-details', '\App\Http\Controllers\PaymentDetailsController@store')->name('profile.payment-details.store')->middleware(['2fa.or.email-verification']);
    Route::post('profile/eth-address', '\App\Http\Controllers\PaymentDetailsController@storeEthAddress')->name('profile.payment-details.store-eth')/*->middleware(['2fa.or.email-verification'])*/;

    Route::get('profile/address-history', '\App\Http\Controllers\PaymentDetailsController@addressHistory')->name('profile.address-history');
});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){
    Route::post('payment-details/mark-as-archive', '\App\Http\Controllers\PaymentDetailsController@markAsArchive')->name('payment-details.mark-as-archive');
    Route::post('payment-details/mark-as-non-archive', '\App\Http\Controllers\PaymentDetailsController@markAsNonArchive')->name('payment-details.mark-as-non-archive');
    Route::post('payment-details/change-status', '\App\Http\Controllers\PaymentDetailsController@changeStatus')->name('payment-details.change-status');
    Route::get('payment-details/{id}', '\App\Http\Controllers\PaymentDetailsController@show')->name('payment-details.show');
    Route::get('payment-details', '\App\Http\Controllers\PaymentDetailsController@index')->name('payment-details.index');

    Route::get('channels/{channel}/address-history', '\App\Http\Controllers\PaymentDetailsController@addressHistory')->name('users.address-history');
});

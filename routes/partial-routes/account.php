<?php

use Illuminate\Support\Facades\Route;


Route::get('confirm-eth-address/{token}', '\App\Http\Controllers\UserController@changeETHAddressConfirmation')->name('confirm-eth-address');

Route::delete('account/delete/{token}', '\App\Http\Controllers\UserController@deleteAccount')->name("account.delete");

// For Login Users
Route::group(['middleware' => 'auth:api'], function(){
    Route::get('profile/2fa', '\App\Http\Controllers\Auth\_2FAController@user2FA')->name('profile.2fa');

    Route::get('profile', '\App\Http\Controllers\UserController@profile');
    Route::post('profile', '\App\Http\Controllers\UserController@updateProfile');
    Route::delete('profile', '\App\Http\Controllers\UserController@destroy')->name('profile.destroy');
    Route::post('profile/password', '\App\Http\Controllers\UserController@changePassword')->middleware(['2fa']);

    Route::get('subscribed-channels', '\App\Http\Controllers\UserController@subscribedChannels');

    // Become A Publisher
    Route::post('publisher/apply', '\App\Http\Controllers\PublisherController@becomeAPublisher')->name('publisher.apply');

    Route::post('profile/eth-address', '\App\Http\Controllers\UserController@changeETHAddress')->name('change-eth-address');

    Route::post('profile/payment-details', '\App\Http\Controllers\IdentifyController@storePaymentDetails')->name('profile.payment-details.store');

    // Delete account
    Route::delete('account/delete', '\App\Http\Controllers\UserController@deleteAccountRequest')->name("account.delete-request");
    
});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    // Delete account
    Route::delete('account/delete', '\App\Http\Controllers\UserController@deleteAccountRequest')->name("account.delete-request");

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

});
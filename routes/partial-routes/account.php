<?php

use Illuminate\Support\Facades\Route;

// For Logged in Users
Route::group(['middleware' => 'auth:api'], function(){
    Route::get('profile/2fa', '\App\Http\Controllers\Auth\_2FAController@user2FA')->name('profile.2fa');

    Route::get('profile', '\App\Http\Controllers\UserController@profile');
    Route::post('profile', '\App\Http\Controllers\UserController@updateProfile');
    Route::delete('profile', '\App\Http\Controllers\UserController@destroy')->name('profile.destroy');
    Route::post('profile/password', '\App\Http\Controllers\UserController@changePassword')->middleware(['2fa']);
    Route::post('profile/custom-feed', '\App\Http\Controllers\CustomFeedController@update');
    Route::get('profile/custom-feed', '\App\Http\Controllers\CustomFeedController@show');

    Route::get('subscribed-channels', '\App\Http\Controllers\UserController@subscribedChannels');

    // Become A Publisher
    Route::post('publisher/apply', '\App\Http\Controllers\PublisherController@becomeAPublisher')->name('publisher.apply');

    Route::post('profile/eth-address', '\App\Http\Controllers\UserController@changeETHAddress')->name('change-eth-address');
    Route::post('profile/wallet-address', '\App\Http\Controllers\UserController@setAuthWallet')->name('set-wallet-address');

    // Delete account
    Route::delete('account/delete', '\App\Http\Controllers\UserController@deleteAccount')->name("account.delete")->middleware(['2fa.or.email-verification']);

});


Route::get('account/restore/{token}', '\App\Http\Controllers\UserController@restoreAccount')->name("account.restore");


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    // Delete account
    Route::delete('account/delete', '\App\Http\Controllers\UserController@deleteAccount')->name("account.delete")->middleware(['2fa.or.email-verification']);
    Route::post('monetize/active-referral-points', '\App\Http\Controllers\ReferralController@setPointsToActive')->name("monetize.active-referral-points");
    Route::get('monetize/referral-statistics', '\App\Http\Controllers\ReferralController@statistics')->name("monetize.referral-statistics");

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

});
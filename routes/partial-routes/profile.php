<?php

use Illuminate\Support\Facades\Route;


Route::get('confirm-eth-address/{token}', '\App\Http\Controllers\UserController@changeETHAddressConfirmation')->name('confirm-eth-address');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::get('profile', '\App\Http\Controllers\UserController@profile');
    Route::post('profile', '\App\Http\Controllers\UserController@updateProfile');
    Route::get('subscribed-channels', '\App\Http\Controllers\UserController@subscribedChannels');

    // Become A Publisher
    Route::post('publisher/apply', '\App\Http\Controllers\PublisherController@becomeAPublisher')->name('publisher.apply');

    Route::post('profile/eth-address', '\App\Http\Controllers\UserController@changeETHAddress')->name('change-eth-address');

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
<?php

use Illuminate\Support\Facades\Route;



// For Login Users
Route::group(['middleware' => 'auth:api'], function(){


});

Route::post('tc-polygon/update-hero-data', '\App\Http\Controllers\TCPolygonController@nftTokenTransfered')->name('tc-polygon.update-hero-data');

Route::post('users/username/check', '\App\Http\Controllers\UserController@usernameCheck')->name('users.username.check');
Route::post('users/referral-code/check', '\App\Http\Controllers\UserController@referralCodeCheck')->name('users.referral-code.check');


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

    Route::get('users', '\App\Http\Controllers\UserController@index')->name('users');
    Route::get('users/{user}', '\App\Http\Controllers\UserController@show')->name('users.show');
    Route::post('users', '\App\Http\Controllers\UserController@store')->name('users.store');
    Route::put('users/{user}/restore', '\App\Http\Controllers\UserController@restoreUser')->name('users.restore');
    Route::put('users/{user}', '\App\Http\Controllers\UserController@update')->name('users.update');
    Route::delete('users/{user}', '\App\Http\Controllers\UserController@destroy')->name('users.destroy');

    Route::get('publishers', '\App\Http\Controllers\UserController@index')->name('publishers');
    Route::get('publishers/{user}', '\App\Http\Controllers\UserController@show')->name('publishers.show');

    Route::get('admins', '\App\Http\Controllers\UserController@index')->name('admins');
    Route::post('admins', '\App\Http\Controllers\UserController@store')->name('admins.store');

    Route::get('publisher-requests', '\App\Http\Controllers\UserController@index')->name('publisher_requests');
    Route::put('publisher-requests/{user}/confirm', '\App\Http\Controllers\PublisherController@confirm')->name('publisher_requests.confirm');
    Route::put('publisher-requests/{user}/reject', '\App\Http\Controllers\PublisherController@reject')->name('publisher_requests.reject');


});
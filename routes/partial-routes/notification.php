<?php

use Illuminate\Support\Facades\Route;



// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::get('notifications', '\App\Http\Controllers\NotificationController@index');
    Route::get('notifications/{id}', '\App\Http\Controllers\NotificationController@show');
    Route::get('notifications/{scope}/count', '\App\Http\Controllers\NotificationController@unReadNotificationsCount')
        ->where('scope', 'admin|publisher|user');
    Route::put('notifications/{scope}/read', '\App\Http\Controllers\NotificationController@allMarkASRead')
        ->where('scope', 'admin|publisher|user');
    Route::put('notifications/{id}/read', '\App\Http\Controllers\NotificationController@markASRead');

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::get('notifications', '\App\Http\Controllers\NotificationController@index')->name('notifications');

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('notifications', '\App\Http\Controllers\NotificationController@index')->name('notifications');
    Route::get('notifications/sent-by-admin', '\App\Http\Controllers\NotificationController@index_sent_by_admin')->name('notifications.sent_by_admin');
    Route::post('notifications/{scope}', '\App\Http\Controllers\NotificationController@store')
        ->where('scope', 'publisher|user')->name('notifications.store');


    Route::get('notifications/{id}', '\App\Http\Controllers\NotificationController@show')->name('notifications.show');
    Route::delete('notifications/{id}', '\App\Http\Controllers\NotificationController@destroy')->name('notifications.destroy');
    Route::put('notifications/{id}/restore', '\App\Http\Controllers\NotificationController@restore')->name('notifications.restore');


});
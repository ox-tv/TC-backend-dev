<?php

use Illuminate\Support\Facades\Route;


Route::get('options/reasons/{key}', '\App\Http\Controllers\OptionController@getReasonsOption')->name("options.reasons.get");


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){


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

    Route::post('options/reasons/{key}', '\App\Http\Controllers\OptionController@setReasonsOption')->name("options.reasons.store");

});
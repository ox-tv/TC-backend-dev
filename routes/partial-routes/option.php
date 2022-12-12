<?php

use Illuminate\Support\Facades\Route;


Route::get('options/reasons/{key}', '\App\Http\Controllers\OptionController@getReasonsOption')->name("options.reasons.get");

Route::get('options/forbidden-words', '\App\Http\Controllers\OptionController@getForbiddenWords')->name("options.forbidden-words.get");


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
    Route::get('options/reasons/{key}', '\App\Http\Controllers\OptionController@getReasonsOption')->name("options.reasons.get");

    Route::post('options/forbidden-words', '\App\Http\Controllers\OptionController@setForbiddenWords')->name("options.forbidden-words.store");

    Route::get('options/ad-spaces', '\App\Http\Controllers\OptionController@getAdSpace')->name("options.ad-spaces.store");
    Route::post('options/ad-spaces', '\App\Http\Controllers\OptionController@setAdSpace')->name("options.ad-spaces.store");
});
<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('tokens', '\App\Http\Controllers\TokenPointController@overview');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function() {


});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('tokens/dashboard', '\App\Http\Controllers\TokenPointController@adminDashboard')->name('tokens.dashboard');

});

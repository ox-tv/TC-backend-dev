<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('tokens', '\App\Http\Controllers\TokenPointController@overview');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('tokens/claim', '\App\Http\Controllers\TokenPointController@claimTokens')->name('tokens.claim');
    Route::get('tokens/claim-requests', '\App\Http\Controllers\TokenPointController@claimTokenRequests')->name('tokens.claim-requests');

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

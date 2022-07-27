<?php

use Illuminate\Support\Facades\Route;



// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::get('identify/verification-url', '\App\Http\Controllers\IdentifyController@getWebUiUrl');

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
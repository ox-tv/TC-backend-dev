<?php

use Illuminate\Support\Facades\Route;


Route::post('forms/contact-us', '\App\Http\Controllers\FormController@storeContactUs')->name('form.contact-us.store');


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

    Route::apiResource('forms', \App\Http\Controllers\FormController::class)->only(['index']);

});
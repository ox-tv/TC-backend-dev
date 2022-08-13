<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;


Route::apiResource('categories', CategoryController::class)->only(['index']);
Route::get('categories/{idOrSlug}', '\App\Http\Controllers\CategoryController@show');


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

    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->only(['store', 'update', 'destroy']);

});

<?php

use Illuminate\Support\Facades\Route;


Route::apiResource('categories', \App\Http\Controllers\CategoryController::class)->only(['index','show']);


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

    Route::apiResource('categories', \App\Http\Controllers\VideoController::class)->only(['store', 'update', 'destroy']);

});
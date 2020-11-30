<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth routes
Route::post('register', '\App\Http\Controllers\Auth\RegisterController@register');
Route::post('login', '\App\Http\Controllers\Auth\LoginController@login');
Route::middleware('auth:api')->get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);

// Video API routes
Route::middleware('auth:api')->apiResource('videos', \App\Http\Controllers\VideoController::class);
Route::get('videos', '\App\Http\Controllers\VideoController@index');

// Video like/dislike routes
Route::middleware('auth:api')->get('videos/{video}/like', '\App\Http\Controllers\UserVideoRelationController@like');
Route::middleware('auth:api')->get('videos/{video}/dislike', '\App\Http\Controllers\UserVideoRelationController@dislike');

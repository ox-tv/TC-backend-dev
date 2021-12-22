<?php

use Illuminate\Support\Facades\Route;


Route::get('comments/{comment}', '\App\Http\Controllers\CommentController@show');
Route::get('videos/{idOrHash}/comments', '\App\Http\Controllers\VideoController@comments');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('videos/{idOrHash}/comments', '\App\Http\Controllers\VideoController@storeComment');
    Route::post('comments/{comment}/reply', '\App\Http\Controllers\CommentController@reply');
    Route::put('comments/{comment}/like', '\App\Http\Controllers\CommentUserRelationController@like');
    Route::put('comments/{comment}/dislike', '\App\Http\Controllers\CommentUserRelationController@dislike');
    Route::put('comments/{comment}/pin', '\App\Http\Controllers\CommentController@pin');
    Route::put('comments/{comment}/unpin', '\App\Http\Controllers\CommentController@unpin');

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::apiResource('comments', \App\Http\Controllers\CommentController::class)->only(['index']);

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::apiResource('comments', \App\Http\Controllers\CommentController::class)->only(['index','destroy']);
    Route::delete('comments/{comment}', '\App\Http\Controllers\CommentController@destroy')->name('comments.destroy');

});
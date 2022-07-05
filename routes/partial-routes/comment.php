<?php

use Illuminate\Support\Facades\Route;


Route::get('comments/{comment}', '\App\Http\Controllers\CommentController@show');
Route::get('videos/{idOrHash}/comments', '\App\Http\Controllers\VideoController@comments');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('videos/{idOrHash}/comments', '\App\Http\Controllers\VideoController@storeComment')->middleware('user.unmute');
    Route::post('comments/{comment}/reply', '\App\Http\Controllers\CommentController@reply')->middleware('user.unmute');
    Route::put('comments/{comment}/like', '\App\Http\Controllers\CommentUserRelationController@like')->middleware('user.unmute');
    Route::put('comments/{comment}/dislike', '\App\Http\Controllers\CommentUserRelationController@dislike')->middleware('user.unmute');
    Route::put('comments/{comment}/pin', '\App\Http\Controllers\CommentController@pin');
    Route::put('comments/{comment}/unpin', '\App\Http\Controllers\CommentController@unpin');
    Route::apiResource('comments', \App\Http\Controllers\CommentController::class)->only(['destroy']);

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::delete('comments/unremember-all', '\App\Http\Controllers\CommentUserRelationController@unrememberAll')->name('comments.unremember-all');
    Route::put('comments/{comment}/remember', '\App\Http\Controllers\CommentUserRelationController@remember')->name('comments.remember');
    Route::put('comments/{comment}/read-all-replies', '\App\Http\Controllers\CommentController@readAllReplies')->name('comments.read-all-replies');
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
<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

// search
Route::get('search/{keyword}', '\App\Http\Controllers\SearchController@index');


Route::middleware('auth:api')->post('upload', '\App\Http\Controllers\UploadController@upload');


// publisher routes
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function() {

    Route::get('s3/pre-signed-url-for-upload-video', '\App\Http\Controllers\S3Controller@getPreSignedURLForUploadVideo')->name('videos.s3.upload.pre_signed_url');

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('.score-board');
});


// Admin api routes
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){
    Route::get('dashboard', '\App\Http\Controllers\GeneralController@adminDashboard')->name('dashboard');

});

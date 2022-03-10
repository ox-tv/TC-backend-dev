<?php

use Illuminate\Support\Facades\Route;


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('upload', '\App\Http\Controllers\UploadController@UploadToS3');

});


// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function(){

    Route::get('s3/pre-signed-url-for-upload-video', '\App\Http\Controllers\S3Controller@getPreSignedURLForUploadVideo')
        ->name('videos.s3.upload.pre_signed_url')->middleware('channel.unfreeze');

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){


});
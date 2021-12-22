<?php

use Illuminate\Support\Facades\Route;




// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::post('videos/{id}/report', '\App\Http\Controllers\ReportController@store');
    Route::post('comments/{id}/report', '\App\Http\Controllers\ReportController@store');

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

    Route::get('reports/video', '\App\Http\Controllers\ReportController@index');
    Route::get('reports/comment', '\App\Http\Controllers\ReportController@index');
    Route::get('reports/video/{id}', '\App\Http\Controllers\ReportController@index_reports')->name("video.reports");
    Route::get('reports/comment/{id}', '\App\Http\Controllers\ReportController@index_reports')->name("comment.reports");

});
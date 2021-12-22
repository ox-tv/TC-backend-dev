<?php

use Illuminate\Support\Facades\Route;


Route::get('lotteries', '\App\Http\Controllers\LotteryController@index')->name('lotteries.index');


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

    Route::get('lotteries', '\App\Http\Controllers\LotteryController@index')->name('lotteries.index');
    Route::post('lotteries', '\App\Http\Controllers\LotteryController@lottery')->name('lotteries.lottery');
    Route::put('lotteries/{lottery_user_id}/paid', '\App\Http\Controllers\LotteryController@setToPaid')->name('lotteries.paid');

});
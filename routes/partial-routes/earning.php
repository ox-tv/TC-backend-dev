<?php

use Illuminate\Support\Facades\Route;




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

    Route::apiResource('earnings', '\App\Http\Controllers\EarningController')->only(['index']);
    Route::get('earnings/total', '\App\Http\Controllers\EarningController@total')->name('earnings.report-total');
    Route::get('earnings/monthly', '\App\Http\Controllers\EarningController@monthly')->name('earnings.report-monthly');
    Route::get('earnings/total-distributed-money', '\App\Http\Controllers\EarningController@getTotalDistributedMoney')->name('earnings.total_distributed_money');
    Route::get('earnings/{earning}/export-as-pdf', '\App\Http\Controllers\EarningController@exportEarningAsPDF')->name('earnings.export-as-pdf');
});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::apiResource('earnings', '\App\Http\Controllers\EarningController')->only(['index']);
    Route::get('earnings/total', '\App\Http\Controllers\EarningController@total')->name('earnings.report-total');
    Route::get('earnings/monthly', '\App\Http\Controllers\EarningController@monthly')->name('earnings.report-monthly');

    Route::put('earnings/total-distributed-money', '\App\Http\Controllers\EarningController@setTotalDistributedMoney')->name('earnings.store_total_distributed_money');
    Route::post('earnings/calc', '\App\Http\Controllers\EarningController@calcEarnings')->name('earnings.calc');
    Route::put('earnings/{earning}/paid', '\App\Http\Controllers\EarningController@setToPaid')->name('earnings.paid');
    Route::get('earnings/total-distributed-money', '\App\Http\Controllers\EarningController@getTotalDistributedMoney')->name('earnings.total_distributed_money');

});
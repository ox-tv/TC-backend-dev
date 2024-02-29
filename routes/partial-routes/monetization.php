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
    Route::get('monetization/qualified-status', '\App\Http\Controllers\MonetizationController@qualifiedStatus')->name('monetization.qualified-status');

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){


    Route::get('monetization/qualified-channels', '\App\Http\Controllers\MonetizationController@qualifiedChannels')->name('monetization.qualified-channels');
    Route::get('monetization/payouts', '\App\Http\Controllers\MonetizationController@payouts')->name('monetization.payouts');
    Route::put('monetization/budget', '\App\Http\Controllers\MonetizationController@setBudget')->name('monetization.store_budget');
    Route::get('monetization/budget', '\App\Http\Controllers\MonetizationController@getBudget')->name('monetization.get_budget');
    Route::get('monetization/payouts/export', '\App\Http\Controllers\MonetizationController@exportMonetizationPayouts')->name('monetization.payouts.export');
    Route::put('monetization/payouts/mark-as-paid', '\App\Http\Controllers\MonetizationController@markAsPaid')->name('monetization.payouts.mark-as-paid');
});
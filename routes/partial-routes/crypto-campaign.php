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

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::apiResource('crypto-campaigns', \App\Http\Controllers\CryptoCampaignController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::get('crypto-campaigns/{campaignId}/statistics', '\App\Http\Controllers\CryptoCampaignController@statistics')->name('crypto-campaigns.statistics');
    Route::get('crypto-campaigns/{campaignId}/statistics/export', '\App\Http\Controllers\CryptoCampaignController@statistics')->name('crypto-campaigns.statistics.export');

});

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

    Route::put('ads/settings', '\App\Http\Controllers\AdController@storeSettings')->name('ads.settings.store');
    Route::get('ads/settings', '\App\Http\Controllers\AdController@getSettings')->name('ads.settings.get');

    Route::get('ads/filled-slots', '\App\Http\Controllers\AdController@filledSlotes')->name('ads.filled-slots');
    Route::post('ads/campaigns', '\App\Http\Controllers\AdController@storeCampaign')->name('ads.campaigns.store');
    Route::put('ads/campaigns/{id}', '\App\Http\Controllers\AdController@updateCampaign')->name('ads.campaigns.update');
    Route::delete('ads/campaigns/{id}', '\App\Http\Controllers\AdController@destroyCampaign')->name('ads.campaigns.destroy');
    Route::get('ads/campaigns/{id}', '\App\Http\Controllers\AdController@showCampaign')->name('ads.campaigns.show');

});

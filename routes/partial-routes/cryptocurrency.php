<?php

use Illuminate\Support\Facades\Route;

Route::get('market/cryptocurrencies', '\App\Http\Controllers\CryptoCurrencyController@index');
Route::get('cryptocurrencies', '\App\Http\Controllers\CryptoCurrencyController@index');
Route::put('cryptocurrencies/{cryptocurrency}/buy/{campaign}', '\App\Http\Controllers\CryptoCampaignController@storeStatistic');


// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    Route::get('cryptocurrencies/favorites', '\App\Http\Controllers\CryptoCurrencyController@favorites');
    Route::put('cryptocurrencies/{cryptocurrency}/add-to-fav', '\App\Http\Controllers\CryptoCurrencyController@addToFavorites');
    Route::put('cryptocurrencies/{cryptocurrency}/remove-from-fav', '\App\Http\Controllers\CryptoCurrencyController@removeFromFavorites');

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

    Route::get('cryptocurrencies/relatedto-campaigns', '\App\Http\Controllers\CryptoCurrencyController@index')->name('cryptocurrencies.relatedto-campaigns');

});
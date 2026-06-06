<?php

use Illuminate\Support\Facades\Route;


// hero membership
Route::apiResource('plans', '\App\Http\Controllers\PlanController')->only(['index']);
Route::apiResource('payment-methods', '\App\Http\Controllers\PaymentMethodController')->only(['index']);



// For Login Users
Route::group(['middleware' => 'auth:api'], function(){

    // stripe
    Route::get('stripe/setup-intent', '\App\Http\Controllers\StripeController@setupIntent');
    Route::delete('stripe/subscription/cancel', '\App\Http\Controllers\StripeController@cancelSubscription');

    Route::post('pricing/{pricing}', '\App\Http\Controllers\HeroMembershipController@store')->name('pricing.store');
    Route::post('pricing/{pricing}/process', '\App\Http\Controllers\HeroMembershipController@processPayment')->name('pricing.processPayment');
    Route::get('pricing/{pricing}/process', '\App\Http\Controllers\HeroMembershipController@processPayment')->name('pricing.processPayment');
    Route::get('profile/pricing', '\App\Http\Controllers\HeroMembershipController@index')->name('profile.pricing');

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

    Route::apiResource('payment-methods', '\App\Http\Controllers\PaymentMethodController')->only(['index']);
    Route::apiResource('plans', '\App\Http\Controllers\PlanController');

    Route::get('memberships', '\App\Http\Controllers\HeroMembershipController@index')->name('memberships.index');
    Route::get('membership/earnings/daily', '\App\Http\Controllers\HeroMembershipController@earningsDaily')->name('membership.earnings.report-daily');
    Route::get('membership/earnings/monthly', '\App\Http\Controllers\HeroMembershipController@earningsMonthly')->name('membership.earnings.report-monthly');
    Route::get('membership/earnings/total', '\App\Http\Controllers\HeroMembershipController@earningsTotal')->name('membership.earnings.report-total');

});
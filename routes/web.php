<?php

use App\Notifications\CustomNotification;
use App\Notifications\TCNotification\TCNotification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/event', function () {
    broadcast(new \App\Events\Hello('say my name'));
});
Route::get('/private-event', function () {
    event(new \App\Events\PrivateHello('private say my name'));
});

Route::get('/notification', function () {
    $user = \App\Models\User::find(12);

    TCNotification::send(collect([$user]),
        new CustomNotification(
            'global',
            'custom',
            ['message' => "my custom notification"]
        )
    );

    return "done";
});

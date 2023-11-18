<?php

use App\TCNotification\GeneralNotification;
use Carbon\Carbon;
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
    //dd(getClientIP(request()), request()->getClientIp());
    return response()->json(['data']);
    return view('welcome');
})->middleware('waf.ratelimit:3,30,s,1,m');




// TODO: Remove testing routes for broadcasting when front-end side finished
Route::get('/event', function () {
    $data = \App\Models\Notification::latest()->first();

    $data->load(['entity','from']);

    $resource = \App\Http\Resources\Notification\NotificationResource::make($data);

    //broadcast(new \App\Events\Hello('say my name'));
    broadcast(new \App\Events\Hello($resource));
});

Route::get('/private-event', function () {
    event(new \App\Events\PrivateHello('private say my name'));
});

Route::get('/notification', function () {
    $users = \App\Models\User::whereIn('id', [12,13])->get();
    TCNotification::Send($users, new GeneralNotification(
        \App\Models\Notification::TYPE_CUSTOM_NOTIFICATION,
        'global',
        ['message' => "test new notification"],
        [
            //'published_at' => \Carbon\Carbon::now()->addDay(),
            'from' => 12,
            'entity_id' => 12,
            'entity_type' => \App\Models\User::class,
        ]
    ));

    return "done";
});

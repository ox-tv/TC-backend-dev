<?php

use App\Models\SecurityRateLimit;
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

    $dateFilter = '2023-11-22';
    $result = (new SecurityRateLimit())
        ->setCollection("rate_limit_{$dateFilter}")->raw(function($collection){
            return $collection->aggregate([
                 ['$group' => [
                     '_id' => ['ip_address' => '$ip_address', 'user_id' => '$user_id'],
                 ]],
                ['$group' => [
                    '_id' => '$_id.ip_address',
                    "users_count" => ['$sum' => 1]
                ]],
                ['$sort' => ['users_count' => -1]],
                ['$match' => [
                    'users_count' => ['$gte'=> 2],
                ]],
            ]);
        })/*->pluck('_id')->toArray()*/;

    return response()->json($result);
    return view('welcome');
})->middleware('waf.ratelimit:4,1,m,1,D');




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

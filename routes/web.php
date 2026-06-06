<?php

use App\Http\Controllers\PlaceholderChannelAvatarController;
use App\TCNotification\GeneralNotification;
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

Route::get('placeholder/channel/{channel}/avatar.svg', PlaceholderChannelAvatarController::class)
    ->name('placeholders.channel_avatar');

/*
 | Local image-thumbnail resizer (demo stand-in for the production CDN/resizer).
 | The API returns resized variant URLs like /storage/<folder>/<w>_<h>/<file>.
 | Those sized files don't exist on local disk, so this route lazily generates a
 | width-resized JPEG (cached to disk) from the original, falling back to the
 | original image if resizing isn't possible.
 */
Route::get('storage/{folder}/{size}/{file}', function (string $folder, string $size, string $file) {
    abort_unless(
        in_array($folder, ['channels', 'videos-thumbnails', 'videos'], true)
        && preg_match('/^(auto|\d+)_(auto|\d+)$/', $size),
        404
    );

    $file = basename($file);
    $original = storage_path("app/public/{$folder}/{$file}");
    abort_unless(is_file($original), 404);

    $mime = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';
    $serve = fn (string $path) => response(file_get_contents($path), 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);

    $target = storage_path("app/public/{$folder}/{$size}/{$file}");
    if (is_file($target)) {
        return $serve($target);
    }

    [$w] = explode('_', $size);
    if (is_numeric($w) && function_exists('imagescale') && str_ends_with(strtolower($file), '.jpg')) {
        try {
            $src = @imagecreatefromjpeg($original);
            if ($src) {
                $scaled = imagescale($src, (int) $w);
                if ($scaled) {
                    @mkdir(dirname($target), 0775, true);
                    imagejpeg($scaled, $target, 85);
                    imagedestroy($scaled);
                }
                imagedestroy($src);
                if (is_file($target)) {
                    return $serve($target);
                }
            }
        } catch (\Throwable $e) {
            // fall through to original
        }
    }

    return $serve($original);
})->where('file', '[^/]+')->name('storage.thumbnail');



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

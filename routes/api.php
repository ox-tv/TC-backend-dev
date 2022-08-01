<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group([], __DIR__.'/partial-routes/general.php');

Route::group([], __DIR__.'/partial-routes/auth.php');

Route::group([], __DIR__.'/partial-routes/_2fa.php');

Route::group([], __DIR__.'/partial-routes/email-verification.php');

Route::group([], __DIR__.'/partial-routes/identify.php');

Route::group([], __DIR__.'/partial-routes/category.php');

Route::group([], __DIR__.'/partial-routes/feedback.php');

Route::group([], __DIR__.'/partial-routes/tag.php');

Route::group([], __DIR__.'/partial-routes/cryptocurrency.php');

Route::group([], __DIR__.'/partial-routes/department.php');

Route::group([], __DIR__.'/partial-routes/lottery.php');

Route::group([], __DIR__.'/partial-routes/message.php');

Route::group([], __DIR__.'/partial-routes/upload.php');

Route::group([], __DIR__.'/partial-routes/earning.php');

Route::group([], __DIR__.'/partial-routes/language.php');

Route::group([], __DIR__.'/partial-routes/notification.php');

Route::group([], __DIR__.'/partial-routes/option.php');

Route::group([], __DIR__.'/partial-routes/report.php');

Route::group([], __DIR__.'/partial-routes/account.php');

Route::group([], __DIR__.'/partial-routes/comment.php');

Route::group([], __DIR__.'/partial-routes/export.php');

Route::group([], __DIR__.'/partial-routes/payment.php');

Route::group([], __DIR__.'/partial-routes/playlist.php');

Route::group([], __DIR__.'/partial-routes/channel.php');

Route::group([], __DIR__.'/partial-routes/role.php');

Route::group([], __DIR__.'/partial-routes/transaction.php');

Route::group([], __DIR__.'/partial-routes/user.php');

Route::group([], __DIR__.'/partial-routes/video.php');


// Utility API routes
Route::group([], function(){
    Route::get('captcha', '\App\Http\Controllers\CaptchaController@get');
    Route::post('captcha', '\App\Http\Controllers\CaptchaController@verify');
});

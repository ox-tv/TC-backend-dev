<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('home', '\App\Http\Controllers\GeneralController@home');

// search
Route::get('search/{keyword}', '\App\Http\Controllers\SearchController@index');

Route::get('test', function (){
    $model = \App\Models\Comment::with([
            'PinnedBy',
            'user',
            'video',
            'replies',
            'reports',
        ])
        ->findOrFail(1786)
        ->append([
            'is_liked',
            'is_disliked',
            'reports_count',
            'likes_count',
            'dislikes_count',
            'replies_count',
            'reason_key',
            'reason_text',
        ]);

    return  \App\Http\Resources\Comment\CommentResource::make($model);
    //return  \App\Http\Resources\User\UserItem::make($model);
    //return  \App\Http\Resources\UserItem::make($model);
});

// For Publishers
Route::group([
    'middleware' => 'auth.role',
    'as' => 'publisher',
    'prefix' => 'publisher',
    'role' => ['publisher', 'admin']
], function() {

    Route::get('score_board', '\App\Http\Controllers\PublisherController@scoreBoard')->name('.score-board');

});


// For Admins
Route::group([
    'middleware' => 'auth.role',
    'as' => 'admin.',
    'prefix' => 'admin',
    'role' => 'admin'
], function(){

    Route::get('dashboard', '\App\Http\Controllers\GeneralController@adminDashboard')->name('dashboard');

});

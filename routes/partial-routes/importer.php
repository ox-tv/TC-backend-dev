<?php

use Illuminate\Support\Facades\Route;

Route::post('importer/test', '\App\Http\Controllers\YoutubeImporterController@storeVideo');

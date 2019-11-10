<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post('batch', '\\LaravelApiBase\\Http\\Controllers\\BatchController@index');
});

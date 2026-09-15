<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// SPA роутинг
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

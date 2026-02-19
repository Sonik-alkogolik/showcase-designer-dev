<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';

Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');

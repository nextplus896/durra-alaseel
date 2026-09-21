<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('frontend.index');
})->name('index');

// Serve storage files without symlink — works locally and in Coolify
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath, [
        'Content-Type' => mime_content_type($fullPath),
    ]);
})->where('path', '.*')->name('storage.file');

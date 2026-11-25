<?php

use App\Http\Controllers\MediaUploadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WriteupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WriteupController::class, 'index'])->name('dashboard');
Route::redirect('/dashboard', '/');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:5,1')
        ->name('profile.destroy');

    Route::get('/postingan-saya', [WriteupController::class, 'mine'])->name('writeups.mine');

    Route::get('/writeups/create', [WriteupController::class, 'create'])->name('writeups.create');
    Route::post('/writeups', [WriteupController::class, 'store'])->name('writeups.store');

    Route::get('/writeups/{writeup}/edit', [WriteupController::class, 'edit'])
        ->middleware('can:update,writeup')
        ->name('writeups.edit');
    Route::put('/writeups/{writeup}', [WriteupController::class, 'update'])
        ->name('writeups.update');
    Route::delete('/writeups/{writeup}', [WriteupController::class, 'destroy'])
        ->middleware(['can:delete,writeup', 'throttle:5,1'])
        ->name('writeups.destroy');
    Route::post('/writeups/media', [MediaUploadController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('writeups.media.store');
});

Route::get('/writeups/{writeup}', [WriteupController::class, 'show'])->name('writeups.show');

Route::get('/test', function () {
    return view('dashboard');
})->name('test');

require __DIR__.'/auth.php';

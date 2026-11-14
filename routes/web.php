<?php

use App\Http\Controllers\MediaUploadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WriteupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WriteupController::class, 'index'])->name('dashboard');
Route::redirect('/dashboard', '/');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/writeups/create', [WriteupController::class, 'create'])->name('writeups.create');
    Route::post('/writeups', [WriteupController::class, 'store'])->name('writeups.store');
    Route::post('/writeups/media', [MediaUploadController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('writeups.media.store');
});

Route::get('/test', function () {
    return view('dashboard');
})->name('test');

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlueskyController;
use App\Http\Controllers\BlueskyLikesController;

Route::get('/', function () {
    return redirect('/login');
})
->name('index');

Route::get('/login', [BlueskyController::class, 'login'])->name('login');
Route::post('/login', [BlueskyController::class, 'doLogin'])->name('login.post');
Route::post('/logout', [BlueskyController::class, 'doLogout'])->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');
    Route::post('/settings/export-posts', [SettingsController::class, 'exportPosts'])->name('settings.exportPosts');
});

Route::get('/{handle}', [BlueskyController::class, 'showProfile'])->name('profile.show');
Route::get('/{handle}/likes', [BlueskyLikesController::class, 'show'])->name('profile.likes');
Route::get('/{handle}/status', [StatusController::class, 'show'])->name('profile.status');
Route::get('/{handle}/replies', [BlueskyController::class, 'showReplies'])->name('profile.replies');
Route::get('/{handle}/hashtags', [App\Http\Controllers\HashtagController::class, 'index'])->name('profile.hashtags');

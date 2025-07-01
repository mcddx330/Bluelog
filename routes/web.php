<?php

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlueskyController;
use App\Http\Controllers\BlueskyLikesController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [BlueskyController::class, 'login'])->name('login');
Route::post('/login', [BlueskyController::class, 'doLogin'])->name('login.post');
//Route::get('/posts/{accessJwt}/{refreshJwt}/{did}', [BlueskyController::class, 'showPosts'])->name('posts.show');
//Route::get('/likes/{accessJwt}/{refreshJwt}/{did}', [BlueskyLikesController::class, 'showLikes'])->name('likes.show');
Route::get('/profile/{handle}/likes', [BlueskyLikesController::class, 'show'])->name('profile.likes');
Route::get('/profile/{handle}', [BlueskyController::class, 'showProfile'])->name('profile.show');
Route::get('/profile/{handle}/status', [StatusController::class, 'show'])->name('profile.status');
Route::get('/profile/{handle}/friends', [BlueskyController::class, 'showFriends'])->name('profile.friends');
Route::get('/profile/{handle}/hashtags', [App\Http\Controllers\HashtagController::class, 'index'])->name('profile.hashtags');

Route::middleware('auth')->group(function () {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');
    Route::post('/settings/export-posts', [SettingsController::class, 'exportPosts'])->name('settings.exportPosts');

Route::post('/logout', [BlueskyController::class, 'doLogout'])->name('logout');
});

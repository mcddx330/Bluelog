<?php

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlueskyController;
use App\Http\Controllers\BlueskyLikesController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\FaqController;


Route::middleware('auth')->group(function () {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings', [SettingsController::class, 'destroy'])->name('settings.destroy');
    Route::post('/settings/export-posts', [SettingsController::class, 'exportPosts'])->name('settings.exportPosts');
    Route::post('/{handle}/full-sync-data', [SettingsController::class, 'fullSyncData'])->name('settings.fullSyncData');
    Route::post('/settings/invitation-code/generate', [SettingsController::class, 'generateInvitationCode'])->name('settings.generateInvitationCode');
    Route::delete('/settings/invitation-code/{invitation_code_id}', [SettingsController::class, 'deleteInvitationCode'])->name('settings.deleteInvitationCode');
    Route::post('/settings/registration-mode', [SettingsController::class, 'updateRegistrationMode'])->name('settings.updateRegistrationMode');
    Route::post('/settings/crawling', [SettingsController::class, 'updateCrawlingSetting'])->name('settings.updateCrawlingSetting');
});

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/login', [BlueskyController::class, 'login'])->name('login');
Route::post('/login', [BlueskyController::class, 'doLogin'])->name('login.post');
Route::post('/logout', [BlueskyController::class, 'doLogout'])->name('logout');
Route::post('/notifications/mark-as-read', [BlueskyController::class, 'markNotificationsAsRead'])->name('notifications.markAsRead');

Route::get('/faq', [FaqController::class, 'index'])->name('faq');

Route::get('/robots.txt', [\App\Http\Controllers\RobotsController::class, 'show'])->name('robots');

Route::get('/{handle}', [BlueskyController::class, 'showProfile'])->name('profile.show');
Route::get('/{handle}/likes', [BlueskyLikesController::class, 'show'])->name('profile.likes');
Route::get('/{handle}/status', [StatusController::class, 'show'])->name('profile.status');
Route::get('/{handle}/replies', [BlueskyController::class, 'showReplies'])->name('profile.replies');
Route::get('/{handle}/hashtags', [App\Http\Controllers\HashtagController::class, 'index'])->name('profile.hashtags');
Route::get('/{handle}/archives', [App\Http\Controllers\BlueskyArchivesController::class, 'show'])->name('profile.archives');
Route::post('/{handle}/update-profile-data', [BlueskyController::class, 'updateProfileData'])->name('profile.updateProfileData');
Route::delete('/posts/{post_id}', [BlueskyController::class, 'deletePost'])->name('posts.delete');

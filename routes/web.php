<?php

use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DirectoryContactController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\ReportApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportReplyController;
use App\Http\Controllers\ReportStatusController;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\MicrosoftSettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('install')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');
    Route::get('/application', [InstallController::class, 'application'])->name('application');
    Route::post('/application', [InstallController::class, 'storeApplication'])->name('application.store');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/finish', [InstallController::class, 'finish'])->name('finish');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');

    Route::get('/auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])->name('auth.microsoft.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('reports.index'))->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/reply', [ReportReplyController::class, 'store'])->name('reports.reply');

    Route::get('/reports/{report}/approve/{token}', [ReportApprovalController::class, 'showLink'])->name('reports.approve.link');
    Route::post('/reports/{report}/approve', [ReportApprovalController::class, 'approve'])->name('reports.approve');
    Route::post('/reports/{report}/reject', [ReportApprovalController::class, 'reject'])->name('reports.reject');
    Route::put('/reports/{report}/status', [ReportStatusController::class, 'update'])->name('reports.status.update');

    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/api/directory/search', [DirectoryContactController::class, 'search'])->name('directory.search');
    Route::get('/api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/branding/logo', [BrandingController::class, 'logo'])->name('branding.logo');

    Route::get('/settings/account', [AccountSettingsController::class, 'edit'])->name('settings.account');
    Route::put('/settings/account', [AccountSettingsController::class, 'update'])->name('settings.account.update');
    Route::put('/settings/account/password', [AccountPasswordController::class, 'update'])->name('settings.account.password');
    Route::delete('/settings/account', [AccountSettingsController::class, 'destroy'])->name('settings.account.destroy');

    Route::middleware('admin')->group(function () {
        Route::get('/settings/app', [AppSettingsController::class, 'edit'])->name('settings.app');
        Route::put('/settings/app', [AppSettingsController::class, 'update'])->name('settings.app.update');
        Route::get('/settings/microsoft', [MicrosoftSettingsController::class, 'edit'])->name('settings.microsoft');
        Route::put('/settings/microsoft', [MicrosoftSettingsController::class, 'update'])->name('settings.microsoft.update');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->whereNumber('user');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->whereNumber('user');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->whereNumber('user');

        Route::get('/recipients', [RecipientController::class, 'index'])->name('recipients.index');
        Route::get('/recipients/create', [RecipientController::class, 'create'])->name('recipients.create');
        Route::post('/recipients', [RecipientController::class, 'store'])->name('recipients.store');
        Route::get('/recipients/export', [RecipientController::class, 'export'])->name('recipients.export');
        Route::post('/recipients/import', [RecipientController::class, 'import'])->name('recipients.import');
        Route::get('/recipients/{recipient}/edit', [RecipientController::class, 'edit'])->name('recipients.edit');
        Route::put('/recipients/{recipient}', [RecipientController::class, 'update'])->name('recipients.update');
        Route::delete('/recipients/{recipient}', [RecipientController::class, 'destroy'])->name('recipients.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');
        Route::put('/routing', [RoutingController::class, 'update'])->name('routing.update');
    });

    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show')->whereNumber('user');
});

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('reports.index');
    }

    return redirect()->route('login');
});

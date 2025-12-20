<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\SubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| Version: v1
*/

// Public Routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Subscription Plans (public)
    Route::get('/subscription/plans', [SubscriptionController::class, 'plans'])->name('subscription.plans');
});

// Protected Routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    // Businesses
    Route::apiResource('businesses', BusinessController::class);

    // Audits
    Route::apiResource('audits', AuditController::class)->only(['index', 'show', 'destroy']);
    Route::post('/audits/trigger', [AuditController::class, 'trigger'])->name('audits.trigger');
    Route::get('/audits/compare', [AuditController::class, 'compare'])->name('audits.compare');

    // Subscriptions
    Route::prefix('subscription')->group(function () {
        Route::get('/current', [SubscriptionController::class, 'current'])->name('subscription.current');
        Route::post('/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::get('/usage', [SubscriptionController::class, 'usage'])->name('subscription.usage');
    });
});

<?php

declare(strict_types=1);

use FilamentOAuth\Http\Controllers\OAuthCallbackController;
use FilamentOAuth\Http\Controllers\OAuthRedirectController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('filament-oauth.route_middleware', ['web']))
    ->prefix(config('filament-oauth.route_prefix', 'filament-oauth'))
    ->name('filament-oauth.')
    ->group(function (): void {
        Route::get('{panel}/redirect/{provider}', OAuthRedirectController::class)->name('redirect');
        Route::get('{panel}/callback/{provider}', OAuthCallbackController::class)->name('callback');
    });

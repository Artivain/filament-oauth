<?php

declare(strict_types=1);

use FilamentOAuth\Http\Controllers\OAuthCallbackController;
use FilamentOAuth\Http\Controllers\OAuthRedirectController;
use Illuminate\Support\Facades\Route;

it('registers multi-panel OAuth routes', function (): void {
    expect(Route::has('filament-oauth.redirect'))->toBeTrue()
        ->and(Route::has('filament-oauth.callback'))->toBeTrue()
        ->and(route('filament-oauth.redirect', ['panel' => 'admin', 'provider' => 'github'], false))
        ->toBe('/filament-oauth/admin/redirect/github')
        ->and(route('filament-oauth.callback', ['panel' => 'guest', 'provider' => 'google'], false))
        ->toBe('/filament-oauth/guest/callback/google');
});

it('points OAuth routes to their invokable controllers', function (): void {
    expect(Route::getRoutes()->getByName('filament-oauth.redirect')?->getAction('uses'))
        ->toBe(OAuthRedirectController::class.'@__invoke')
        ->and(Route::getRoutes()->getByName('filament-oauth.callback')?->getAction('uses'))
        ->toBe(OAuthCallbackController::class.'@__invoke');
});

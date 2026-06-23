<?php

declare(strict_types=1);

use FilamentOAuth\Panel\OAuthPanelConfigResolver;
use FilamentOAuth\Panel\OAuthPanelRegistry;

it('merges the default package configuration', function (): void {
    expect(config('filament-oauth.route_prefix'))->toBe('filament-oauth')
        ->and(config('filament-oauth.accounts.table'))->toBe('oauth_accounts')
        ->and(config('filament-oauth.accounts.scope_by_panel'))->toBeTrue()
        ->and(config('filament-oauth.defaults.create_user'))->toBeFalse();
});

it('registers panel-aware services in the container', function (): void {
    expect(app(OAuthPanelRegistry::class))->toBeInstanceOf(OAuthPanelRegistry::class)
        ->and(app(OAuthPanelConfigResolver::class))->toBeInstanceOf(OAuthPanelConfigResolver::class);
});

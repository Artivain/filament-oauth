<?php

declare(strict_types=1);

namespace FilamentOAuth;

use FilamentOAuth\Panel\OAuthPanelConfigResolver;
use FilamentOAuth\Panel\OAuthPanelRegistry;
use FilamentOAuth\Socialite\SocialiteConfigurator;
use FilamentOAuth\User\OAuthUserResolver;
use Illuminate\Support\ServiceProvider;

final class FilamentOAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-oauth.php', 'filament-oauth');

        $this->app->singleton(OAuthPanelRegistry::class);
        $this->app->singleton(OAuthPanelConfigResolver::class);
        $this->app->singleton(SocialiteConfigurator::class);
        $this->app->singleton(OAuthUserResolver::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/filament-oauth.php' => config_path('filament-oauth.php'),
        ], 'filament-oauth-config');
    }
}

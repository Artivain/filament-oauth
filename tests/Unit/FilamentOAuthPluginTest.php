<?php

declare(strict_types=1);

use FilamentOAuth\FilamentOAuthPlugin;

it('builds a Nextcloud SSO panel configuration', function (): void {
    $plugin = FilamentOAuthPlugin::make()
        ->nextcloud('https://cloud.example.com', 'client-id', 'client-secret')
        ->sso('nextcloud')
        ->disableFallbackLogin()
        ->createUsers(false)
        ->syncUsers();

    expect($plugin->getId())->toBe('filament-oauth')
        ->and($plugin->configuration())->toMatchArray([
            'mode' => 'sso',
            'default_provider' => 'nextcloud',
            'fallback_login' => false,
            'providers' => [
                'nextcloud' => [
                    'driver' => 'oidc',
                    'label' => 'Nextcloud',
                    'base_url' => 'https://cloud.example.com',
                    'client_id' => 'client-id',
                    'client_secret' => 'client-secret',
                    'scopes' => ['openid', 'profile', 'email'],
                    'discovery' => true,
                ],
            ],
            'sso' => [
                'auto_redirect' => true,
            ],
            'user' => [
                'create' => false,
                'sync' => true,
            ],
        ]);
});

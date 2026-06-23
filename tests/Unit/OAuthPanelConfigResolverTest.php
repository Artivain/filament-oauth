<?php

declare(strict_types=1);

use FilamentOAuth\Enums\OAuthMode;
use FilamentOAuth\FilamentOAuthPlugin;
use FilamentOAuth\Panel\OAuthPanelConfigResolver;
use FilamentOAuth\Panel\OAuthPanelRegistry;
use Illuminate\Support\Facades\Config;

it('resolves effective panel configuration from defaults, config and plugin overrides', function (): void {
    Config::set('filament-oauth.panels.admin', [
        'mode' => 'single_provider',
        'default_provider' => 'github',
        'fallback_login' => true,
        'providers' => [
            'github' => [
                'driver' => 'socialite',
                'label' => 'GitHub from config',
            ],
        ],
        'user' => [
            'create_user' => false,
        ],
    ]);

    $plugin = FilamentOAuthPlugin::make()
        ->nextcloud('https://cloud.example.com', 'client-id', 'client-secret')
        ->sso('nextcloud')
        ->disableFallbackLogin()
        ->createUsers(false)
        ->allowedEmailDomains(['company.com']);

    $context = app(OAuthPanelConfigResolver::class)->resolveForPlugin('admin', $plugin);

    expect($context->panelId())->toBe('admin')
        ->and($context->provider())->toBe('nextcloud')
        ->and($context->guard())->toBe('web')
        ->and($context->mode())->toBe(OAuthMode::Sso)
        ->and($context->fallbackLoginEnabled())->toBeFalse()
        ->and($context->providerConfig())->toMatchArray([
            'driver' => 'oidc',
            'base_url' => 'https://cloud.example.com',
        ])
        ->and($context->configuration()['providers']['github'])->toMatchArray([
            'driver' => 'socialite',
            'label' => 'GitHub from config',
        ])
        ->and($context->configuration()['providers']['nextcloud']['driver'])->toBe('oidc')
        ->and($context->configuration()['security']['allowed_email_domains'])->toBe(['company.com']);
});

it('rejects providers that are not configured for a panel', function (): void {
    $resolver = new OAuthPanelConfigResolver(new OAuthPanelRegistry);
    $plugin = FilamentOAuthPlugin::make()->providers(['github' => ['driver' => 'socialite']]);

    $resolver->resolveForPlugin('guest', $plugin, provider: 'nextcloud');
})->throws(InvalidArgumentException::class, 'Provider [nextcloud] is not configured for this Filament panel.');

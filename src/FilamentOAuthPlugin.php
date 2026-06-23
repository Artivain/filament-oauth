<?php

declare(strict_types=1);

namespace FilamentOAuth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use FilamentOAuth\Panel\OAuthPanelRegistry;

final class FilamentOAuthPlugin implements Plugin
{
    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'filament-oauth';
    }

    public function register(Panel $panel): void
    {
        app(OAuthPanelRegistry::class)->register($panel, $this);
    }

    public function boot(Panel $panel): void
    {
        // Reserved for later panel-aware bootstrapping.
    }

    /**
     * @param  array<int|string, mixed>  $providers
     */
    public function providers(array $providers): self
    {
        $this->configuration['providers'] = $providers;

        return $this;
    }

    public function buttons(): self
    {
        $this->configuration['mode'] = 'buttons';

        return $this;
    }

    public function singleProvider(string $provider): self
    {
        $this->configuration['mode'] = 'single_provider';
        $this->configuration['default_provider'] = $provider;

        return $this;
    }

    public function sso(string $provider): self
    {
        $this->configuration['mode'] = 'sso';
        $this->configuration['default_provider'] = $provider;
        $this->configuration['sso']['auto_redirect'] = true;

        return $this;
    }

    public function allowFallbackLogin(): self
    {
        $this->configuration['fallback_login'] = true;

        return $this;
    }

    public function disableFallbackLogin(): self
    {
        $this->configuration['fallback_login'] = false;

        return $this;
    }

    public function createUsers(bool $createUsers = true): self
    {
        $this->configuration['user']['create'] = $createUsers;

        return $this;
    }

    public function syncUsers(bool $syncUsers = true): self
    {
        $this->configuration['user']['sync'] = $syncUsers;

        return $this;
    }

    /**
     * @param  array<int, string>  $domains
     */
    public function allowedEmailDomains(array $domains): self
    {
        $this->configuration['security']['allowed_email_domains'] = $domains;

        return $this;
    }

    public function nextcloud(string $url, string $clientId, string $clientSecret): self
    {
        $this->configuration['providers']['nextcloud'] = [
            'driver' => 'oidc',
            'label' => 'Nextcloud',
            'base_url' => $url,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scopes' => ['openid', 'profile', 'email'],
            'discovery' => true,
        ];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        return $this->configuration;
    }
}

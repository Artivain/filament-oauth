<?php

declare(strict_types=1);

namespace FilamentOAuth\Socialite;

use FilamentOAuth\Panel\OAuthPanelContext;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;

final class SocialiteConfigurator
{
    public function __construct(
        private readonly SocialiteFactory $socialite,
    ) {}

    public function redirect(OAuthPanelContext $context): mixed
    {
        return $this->provider($context)->redirect();
    }

    public function user(OAuthPanelContext $context): User
    {
        return $this->provider($context)->user();
    }

    private function provider(OAuthPanelContext $context): Provider
    {
        $provider = $context->provider();

        if ($provider === null) {
            throw new InvalidStateException;
        }

        config()->set("services.{$provider}", array_filter([
            'client_id' => $context->providerConfig()['client_id'] ?? null,
            'client_secret' => $context->providerConfig()['client_secret'] ?? null,
            'redirect' => $context->providerConfig()['redirect_uri'] ?? route('filament-oauth.callback', [
                'panel' => $context->panelId(),
                'provider' => $provider,
            ]),
        ]));

        $socialiteProvider = $this->socialite->driver($provider);

        if ($socialiteProvider instanceof AbstractProvider && ($context->providerConfig()['stateless'] ?? false)) {
            return $socialiteProvider->stateless();
        }

        return $socialiteProvider;
    }
}

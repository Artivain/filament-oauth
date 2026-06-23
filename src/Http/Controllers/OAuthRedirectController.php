<?php

declare(strict_types=1);

namespace FilamentOAuth\Http\Controllers;

use FilamentOAuth\Panel\OAuthPanelConfigResolver;
use FilamentOAuth\Socialite\SocialiteConfigurator;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class OAuthRedirectController
{
    public function __invoke(
        string $panel,
        string $provider,
        OAuthPanelConfigResolver $resolver,
        SocialiteConfigurator $socialite,
    ): RedirectResponse {
        $context = $resolver->resolve($panel, $provider);

        return $socialite->redirect($context);
    }
}

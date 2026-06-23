<?php

declare(strict_types=1);

namespace FilamentOAuth\Http\Controllers;

use FilamentOAuth\Panel\OAuthPanelConfigResolver;
use FilamentOAuth\Socialite\SocialiteConfigurator;
use FilamentOAuth\User\OAuthUserResolver;
use Illuminate\Contracts\Auth\StatefulGuard;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class OAuthCallbackController
{
    public function __invoke(
        string $panel,
        string $provider,
        OAuthPanelConfigResolver $resolver,
        SocialiteConfigurator $socialite,
        OAuthUserResolver $users,
    ): RedirectResponse {
        $context = $resolver->resolve($panel, $provider);
        $user = $users->resolve($context, $socialite->user($context));

        $guard = auth($context->guard());

        if (! $guard instanceof StatefulGuard) {
            throw new RuntimeException('The configured Filament guard must be stateful to complete OAuth login.');
        }

        $guard->login($user);

        return redirect()->intended('/'.$panel);
    }
}

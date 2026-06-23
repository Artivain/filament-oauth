<?php

declare(strict_types=1);

namespace FilamentOAuth\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use FilamentOAuth\Panel\OAuthPanelContext;

final class OAuthLoginSuccessful
{
    public function __construct(
        public readonly OAuthPanelContext $context,
        public readonly Authenticatable $user,
    ) {}
}

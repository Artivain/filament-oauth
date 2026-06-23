<?php

declare(strict_types=1);

namespace FilamentOAuth\Events;

use FilamentOAuth\Panel\OAuthPanelContext;
use Throwable;

final class OAuthCallbackFailed
{
    public function __construct(
        public readonly OAuthPanelContext $context,
        public readonly Throwable $exception,
    ) {}
}

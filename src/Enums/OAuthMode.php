<?php

declare(strict_types=1);

namespace FilamentOAuth\Enums;

enum OAuthMode: string
{
    case Buttons = 'buttons';
    case SingleProvider = 'single_provider';
    case Sso = 'sso';

    public static function fromConfig(?string $mode): self
    {
        return self::tryFrom($mode ?? '') ?? self::Buttons;
    }
}

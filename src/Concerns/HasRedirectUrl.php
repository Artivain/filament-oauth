<?php

declare(strict_types=1);

namespace FilamentOAuth\Concerns;

final class HasRedirectUrl
{
    public static function getRedirectUrl(string $panel): string
    {
        return session()->pull('filament-oauth.redirect_to') ?? '/' . $panel;
    }

    public static function setRedirectUrl(string $url): void
    {
        session()->put('filament-oauth.redirect_to', $url);
    }
}

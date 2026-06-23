<?php

declare(strict_types=1);

namespace FilamentOAuth\Rules;

use FilamentOAuth\Panel\OAuthPanelContext;
use Illuminate\Contracts\Validation\Rule;

final class EmailDomainRule implements Rule
{
    public function __construct(
        private readonly OAuthPanelContext $context,
    ) {}

    public function passes($attribute, $value): bool
    {
        $allowedDomains = $this->context->configuration()['security']['allowed_email_domains'] ?? [];

        if (empty($allowedDomains)) {
            return true;
        }

        if (!is_string($value) || !str_contains($value, '@')) {
            return false;
        }

        $domain = substr($value, strrpos($value, '@') + 1);

        return in_array($domain, $allowedDomains, true);
    }

    public function message(): string
    {
        return 'The email domain is not allowed.';
    }
}

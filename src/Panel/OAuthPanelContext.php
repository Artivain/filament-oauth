<?php

declare(strict_types=1);

namespace FilamentOAuth\Panel;

use Filament\Panel;
use FilamentOAuth\Enums\OAuthMode;

final readonly class OAuthPanelContext
{
    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $providerConfig
     */
    public function __construct(
        private string $panelId,
        private ?Panel $panel,
        private ?string $provider,
        private string $guard,
        private OAuthMode $mode,
        private bool $fallbackLoginEnabled,
        private array $providerConfig,
        private array $configuration,
    ) {}

    public function panelId(): string
    {
        return $this->panelId;
    }

    public function panel(): ?Panel
    {
        return $this->panel;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function guard(): string
    {
        return $this->guard;
    }

    public function mode(): OAuthMode
    {
        return $this->mode;
    }

    public function fallbackLoginEnabled(): bool
    {
        return $this->fallbackLoginEnabled;
    }

    /**
     * @return array<string, mixed>
     */
    public function providerConfig(): array
    {
        return $this->providerConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        return $this->configuration;
    }
}

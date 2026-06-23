<?php

declare(strict_types=1);

namespace FilamentOAuth\Panel;

use Filament\Panel;
use FilamentOAuth\Enums\OAuthMode;
use FilamentOAuth\FilamentOAuthPlugin;
use InvalidArgumentException;

final class OAuthPanelConfigResolver
{
    public function __construct(
        private readonly OAuthPanelRegistry $registry,
    ) {}

    public function resolve(string $panelId, ?string $provider = null): OAuthPanelContext
    {
        return $this->resolveForPlugin(
            panelId: $panelId,
            plugin: $this->registry->plugin($panelId),
            panel: $this->registry->panel($panelId),
            provider: $provider,
        );
    }

    public function resolveForPlugin(
        string $panelId,
        FilamentOAuthPlugin $plugin,
        ?Panel $panel = null,
        ?string $provider = null,
    ): OAuthPanelContext {
        $configuration = $this->effectiveConfiguration($panelId, $plugin->configuration());
        $defaultProvider = $this->stringValue($configuration['default_provider'] ?? null);
        $provider ??= $defaultProvider;

        return new OAuthPanelContext(
            panelId: $panelId,
            panel: $panel,
            provider: $provider,
            guard: $panel?->getAuthGuard() ?? config('auth.defaults.guard', 'web'),
            mode: OAuthMode::fromConfig($this->stringValue($configuration['mode'] ?? null)),
            fallbackLoginEnabled: (bool) ($configuration['fallback_login'] ?? true),
            providerConfig: $this->providerConfig($configuration, $provider),
            configuration: $configuration,
        );
    }

    /**
     * @param  array<string, mixed>  $pluginConfiguration
     * @return array<string, mixed>
     */
    public function effectiveConfiguration(string $panelId, array $pluginConfiguration): array
    {
        $globalDefaults = config('filament-oauth.defaults', []);
        $panelConfiguration = config("filament-oauth.panels.{$panelId}", []);

        return array_replace_recursive(
            [
                'mode' => 'buttons',
                'default_provider' => null,
                'fallback_login' => true,
                'providers' => [],
                'user' => is_array($globalDefaults) ? $globalDefaults : [],
                'security' => [
                    'allowed_email_domains' => [],
                ],
            ],
            is_array($panelConfiguration) ? $panelConfiguration : [],
            $pluginConfiguration,
        );
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    private function providerConfig(array $configuration, ?string $provider): array
    {
        if ($provider === null || $provider === '') {
            return [];
        }

        $providers = $configuration['providers'] ?? [];

        if (! is_array($providers) || ! array_key_exists($provider, $providers)) {
            throw new InvalidArgumentException("Provider [{$provider}] is not configured for this Filament panel.");
        }

        $providerConfiguration = $providers[$provider];

        if (is_string($providerConfiguration)) {
            return ['name' => $providerConfiguration];
        }

        if (! is_array($providerConfiguration)) {
            throw new InvalidArgumentException("Provider [{$provider}] configuration must be an array or string.");
        }

        return $providerConfiguration;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}

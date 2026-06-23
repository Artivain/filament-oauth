<?php

declare(strict_types=1);

namespace FilamentOAuth\Panel;

use Filament\Panel;
use FilamentOAuth\FilamentOAuthPlugin;
use InvalidArgumentException;

final class OAuthPanelRegistry
{
    /**
     * @var array<string, FilamentOAuthPlugin>
     */
    private array $plugins = [];

    /**
     * @var array<string, Panel>
     */
    private array $panels = [];

    public function register(Panel $panel, FilamentOAuthPlugin $plugin): void
    {
        $panelId = $panel->getId();

        $this->plugins[$panelId] = $plugin;
        $this->panels[$panelId] = $panel;
    }

    public function plugin(string $panelId): FilamentOAuthPlugin
    {
        return $this->plugins[$panelId]
            ?? throw new InvalidArgumentException("No Filament OAuth plugin is registered for panel [{$panelId}].");
    }

    public function panel(string $panelId): Panel
    {
        return $this->panels[$panelId]
            ?? throw new InvalidArgumentException("No Filament panel is registered for panel [{$panelId}].");
    }

    public function has(string $panelId): bool
    {
        return isset($this->plugins[$panelId]);
    }

    /**
     * @return array<int, string>
     */
    public function panelIds(): array
    {
        return array_keys($this->plugins);
    }
}

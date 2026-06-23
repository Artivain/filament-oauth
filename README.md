# Filament OAuth

Filament OAuth is an open source plugin to add OAuth2, OpenID Connect and SSO authentication to Filament 5 panels.

The project goal is to provide a simple integration for common providers like Google or GitHub, while supporting self-hosted and enterprise environments, notably Nextcloud, Keycloak, Authentik or Zitadel.

## Project Objectives

- Add OAuth2 and OpenID Connect connections to Filament 5 panels.
- Support multiple Filament panels in the same application.
- Configure each panel directly in its `PanelProvider`.
- Provide a Nextcloud preset usable as OAuth/OIDC provider.
- Allow exclusive SSO mode with automatic redirection to a single provider.
- Keep an extensible architecture for Socialite, OIDC and custom providers.
- Secure account linking via a dedicated `oauth_accounts` table.

## Architecture Principles

### Configuration by `PanelProvider`

The project prioritizes configuration local to each Filament panel.

Each panel should be able to independently define:

- its OAuth/OIDC providers;
- its authentication mode;
- its default provider;
- its guard;
- its user creation rules;
- its user synchronization rules;
- its security rules;
- its SSO behavior;
- its fallback to local login.

This approach is preferred over a single global configuration, as it better respects Filament's multi-panel model.

```php
use Vendor\FilamentOAuth\FilamentOAuthPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->plugins([
            FilamentOAuthPlugin::make()
                ->nextcloud(
                    url: env('NEXTCLOUD_URL'),
                    clientId: env('NEXTCLOUD_ADMIN_CLIENT_ID'),
                    clientSecret: env('NEXTCLOUD_ADMIN_CLIENT_SECRET'),
                )
                ->sso('nextcloud')
                ->disableFallbackLogin()
                ->createUsers(false)
                ->allowedEmailDomains(['company.com']),
        ]);
}
```

### Minimal Global Configuration

The `config/filament-oauth.php` file will mainly define default values and shared options.

```php
return [
    'route_prefix' => 'filament-oauth',

    'accounts' => [
        'table' => 'oauth_accounts',
        'scope_by_panel' => true,
    ],

    'defaults' => [
        'create_user' => false,
        'sync_user' => true,
        'link_by_email' => true,
        'require_verified_email' => true,
    ],
];
```

Priority order:

1. explicit configuration of the plugin in the `PanelProvider`;
2. eventual specific configuration in the config file;
3. global default values;
4. internal package values.

## Use Cases to Support

### Classic OAuth Login

A panel can display multiple login buttons.

```php
FilamentOAuthPlugin::make()
    ->providers(['google', 'github'])
    ->buttons()
    ->allowFallbackLogin()
    ->createUsers(true);
```

### Nextcloud as Provider

The plugin should offer a Nextcloud preset on top of the OIDC driver.

```php
FilamentOAuthPlugin::make()
    ->nextcloud(
        url: env('NEXTCLOUD_URL'),
        clientId: env('NEXTCLOUD_CLIENT_ID'),
        clientSecret: env('NEXTCLOUD_CLIENT_SECRET'),
    )
    ->buttons();
```

Expected environment variables:

```env
NEXTCLOUD_URL=https://cloud.example.com
NEXTCLOUD_CLIENT_ID=filament-admin
NEXTCLOUD_CLIENT_SECRET=secret
```

### Single Provider

A panel can be limited to a single provider.

```php
FilamentOAuthPlugin::make()
    ->nextcloud(
        url: env('NEXTCLOUD_URL'),
        clientId: env('NEXTCLOUD_CLIENT_ID'),
        clientSecret: env('NEXTCLOUD_CLIENT_SECRET'),
    )
    ->singleProvider('nextcloud')
    ->allowFallbackLogin();
```

### Seamless SSO

A panel can automatically redirect unauthenticated users to the configured provider.

```php
FilamentOAuthPlugin::make()
    ->nextcloud(
        url: env('NEXTCLOUD_URL'),
        clientId: env('NEXTCLOUD_ADMIN_CLIENT_ID'),
        clientSecret: env('NEXTCLOUD_ADMIN_CLIENT_SECRET'),
    )
    ->sso('nextcloud')
    ->disableFallbackLogin();
```

This mode is intended for intranet, enterprise or self-hosted integrations, for example a Filament panel integrated into a Nextcloud ecosystem.

### Multi-panels with Different Configurations

Example target with an `admin` panel in strict Nextcloud SSO and a `guest` panel with classic social login.

```php
// AdminPanelProvider.php

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->authGuard('admin')
        ->plugins([
            FilamentOAuthPlugin::make()
                ->nextcloud(
                    url: env('NEXTCLOUD_URL'),
                    clientId: env('NEXTCLOUD_ADMIN_CLIENT_ID'),
                    clientSecret: env('NEXTCLOUD_ADMIN_CLIENT_SECRET'),
                )
                ->sso('nextcloud')
                ->disableFallbackLogin()
                ->createUsers(false)
                ->syncUsers(true)
                ->allowedEmailDomains(['company.com']),
        ]);
}
```

```php
// GuestPanelProvider.php

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('guest')
        ->path('guest')
        ->authGuard('web')
        ->plugins([
            FilamentOAuthPlugin::make()
                ->providers(['google', 'github'])
                ->buttons()
                ->allowFallbackLogin()
                ->createUsers(true),
        ]);
}
```

## Planned Drivers

### Socialite Driver

For common OAuth providers:

- Google;
- GitHub;
- GitLab;
- Facebook;
- Microsoft;
- other Socialite providers.

### OIDC Driver

For OpenID Connect providers and self-hosted environments:

- Nextcloud;
- Keycloak;
- Authentik;
- Authelia;
- Zitadel;
- Azure AD / Entra ID;
- Okta;
- Auth0.

### Custom Driver

The package should allow adding a custom provider without modifying the core plugin.

## Planned Routes

Routes should include the panel identifier to avoid multi-panel collisions.

```text
GET /filament-oauth/{panel}/redirect/{provider}
GET /filament-oauth/{panel}/callback/{provider}
GET /filament-oauth/{panel}/error
POST /filament-oauth/{panel}/logout
```

Examples:

```text
GET /filament-oauth/admin/redirect/nextcloud
GET /filament-oauth/admin/callback/nextcloud
GET /filament-oauth/guest/redirect/github
GET /filament-oauth/guest/callback/github
```

## Panel Context

The core of the package should resolve an OAuth context specific to the current panel.

```php
final class OAuthPanelContext
{
    public function panelId(): string;

    public function panel(): Panel;

    public function provider(): string;

    public function guard(): string;

    public function mode(): OAuthMode;

    public function fallbackLoginEnabled(): bool;

    public function providerConfig(): array;
}
```

This context will be used for:

- resolving effective configuration;
- generating redirect URIs;
- validating that a provider is authorized on a panel;
- logging in the user with the correct guard;
- managing fallback login;
- managing errors and redirections per panel.

## OAuth Account Storage

The project will use a dedicated `oauth_accounts` table.

Planned fields:

```text
oauth_accounts
- id
- user_id
- panel_id
- provider
- provider_user_id
- subject
- email
- email_verified
- name
- nickname
- avatar_url
- access_token
- refresh_token
- token_expires_at
- raw_user
- created_at
- updated_at
```

By default, accounts will likely be scoped by panel.

```php
'accounts' => [
    'scope_by_panel' => true,
],
```

This avoids collisions if two panels use the same provider with different rules.

## User Resolution

Recommended resolution order:

1. resolve the panel and provider;
2. verify that the provider is authorized for this panel;
3. fetch the OAuth/OIDC profile;
4. search for an existing OAuth account via `panel_id`, `provider` and `provider_user_id` or `sub`;
5. search by email if email linking is authorized;
6. create the user if automatic creation is authorized;
7. synchronize authorized attributes;
8. log in the user with the panel guard;
9. redirect to the intended URL of the panel.

## Security

Planned options:

```php
FilamentOAuthPlugin::make()
    ->requireVerifiedEmail()
    ->allowedEmailDomains(['company.com'])
    ->disableAccountLinkingByEmail()
    ->createUsers(false);
```

The package should handle:

- OAuth state validation;
- OIDC nonce;
- protection against loops in SSO mode;
- unauthorized providers per panel;
- missing email;
- unverified email;
- authorized email domains;
- controlled account linking;
- callback errors;
- safe redirects after login.

## Logout

Logout should be configurable per panel.

Planned strategies:

- local logout only;
- local logout with intermediate page;
- federated OIDC logout if the provider supports it.

Local logout only can be problematic in seamless SSO mode, as the user can be immediately reconnected by the provider. The project should therefore provide a strategy for this case.

## Roadmap

### Phase 1 — Package Foundation

- Initialize the Laravel package.
- Create the service provider.
- Create the Filament 5 plugin.
- Publish the configuration.
- Add Laravel Pint, Pest/PHPUnit and static analysis.
- Set up GitHub Actions.

### Phase 2 — Panel-aware Architecture

- Create `OAuthPanelContext`.
- Create an OAuth panels registry.
- Resolve effective configuration per panel.
- Resolve guard from Filament panel.
- Define configuration priority order.

### Phase 3 — Multi-panel Routes

- Add routes with `{panel}` and `{provider}`.
- Generate redirect URIs per panel.
- Store panel and provider in OAuth state.
- Validate callbacks per panel.
- Plan error routes and logout routes per panel.

### Phase 4 — Storage and User Resolution

- Add `oauth_accounts` migration.
- Add `scope_by_panel` option.
- Implement resolution by provider user id or OIDC subject.
- Implement controlled email linking.
- Implement optional user creation.
- Implement optional user synchronization.

### Phase 5 — Socialite Driver

- Add Socialite driver.
- Support Google and GitHub first.
- Display OAuth buttons on Filament login page.
- Test OAuth callbacks and errors.

### Phase 6 — OIDC Driver

- Add generic OpenID Connect driver.
- Support discovery `.well-known/openid-configuration`.
- Handle authorization endpoint, token endpoint and userinfo endpoint.
- Map OIDC claims to user model.
- Handle `sub`, `email`, `email_verified`, `name` and `picture`.

### Phase 7 — Nextcloud Preset

- Add `nextcloud()` helper.
- Document creation of OAuth/OIDC client in Nextcloud.
- Handle redirect URIs per panel.
- Test Nextcloud with mocked endpoints.
- Provide admin/guest configuration example.

### Phase 8 — Login Modes, Single Provider and SSO

- Implement `buttons` mode.
- Implement `single_provider` mode.
- Implement `sso` mode with auto-redirection.
- Add configurable fallback login.
- Add anti-loop protection.
- Handle errors per panel.

### Phase 9 — Advanced Security

- Add email domain allowlist.
- Add verified email rules.
- Add email linking rules.
- Add customizable callbacks.
- Add authentication events.

### Phase 10 — Documentation and Release

- Write complete installation documentation.
- Document configuration by `PanelProvider`.
- Document Nextcloud.
- Document seamless SSO mode.
- Document multi-panel environments.
- Add `CHANGELOG.md`.
- Add `CONTRIBUTING.md`.
- Publish a first beta version.

## Recommended MVP

The first usable version should include:

1. Filament 5 plugin;
2. configuration by `PanelProvider`;
3. multi-panel routes;
4. `oauth_accounts` table;
5. Socialite driver with Google and GitHub;
6. minimal OIDC driver;
7. Nextcloud preset;
8. login mode with buttons;
9. single provider mode;
10. seamless SSO mode;
11. configurable fallback login;
12. basic multi-panel tests.

## Tests to Plan

- `admin` panel in strict Nextcloud SSO.
- `guest` panel with Google/GitHub and fallback login.
- Provider authorized on one panel but denied on another.
- Different guards per panel.
- Different redirect URI per panel.
- Callback to wrong panel rejected.
- User created on `guest` but denied on `admin`.
- Email linking authorized or denied per panel.
- Protection against SSO loops.
- Handling of unverified OIDC email.

## License

The project is planned as an open source package. The target license is GNU AGPL.

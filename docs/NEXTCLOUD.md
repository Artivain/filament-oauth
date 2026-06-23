# Nextcloud OAuth Setup

Filament OAuth provides first-class support for Nextcloud as an OpenID Connect provider through the `nextcloud()` helper method.

## Prerequisites

- Nextcloud instance (16.0 or later)
- Admin access to configure OAuth clients
- Filament application with filament-oauth installed

## Step 1: Create OAuth Client in Nextcloud

### In Nextcloud Admin Panel

1. Log in as administrator
2. Navigate to **Settings** → **Administration** → **Security**
3. Scroll to **OAuth 2.0 clients** section
4. Click **Add client**
5. Fill in the following information:
   - **Name**: `Filament Admin` (or your preferred name)
   - **Redirect URI**: `https://your-filament-app.local/filament-oauth/admin/callback/nextcloud`
6. Click **Add**
7. You'll receive `Client ID` and `Client Secret` - **save these securely**

### For Multiple Panels

If you have multiple Filament panels using Nextcloud, create separate OAuth clients:

- Client 1: Admin Panel → `https://your-app.local/filament-oauth/admin/callback/nextcloud`
- Client 2: Guest Panel → `https://your-app.local/filament-oauth/guest/callback/nextcloud`

## Step 2: Configure Environment Variables

Add to your `.env` file:

```env
NEXTCLOUD_URL=https://cloud.example.com
NEXTCLOUD_ADMIN_CLIENT_ID=your-client-id-here
NEXTCLOUD_ADMIN_CLIENT_SECRET=your-client-secret-here
```

For multiple panels:

```env
NEXTCLOUD_URL=https://cloud.example.com
NEXTCLOUD_ADMIN_CLIENT_ID=admin-client-id
NEXTCLOUD_ADMIN_CLIENT_SECRET=admin-client-secret
NEXTCLOUD_GUEST_CLIENT_ID=guest-client-id
NEXTCLOUD_GUEST_CLIENT_SECRET=guest-client-secret
```

## Step 3: Configure Filament Panel Provider

### Basic SSO Setup (Admin Panel)

For a strict admin panel with SSO and no fallback login:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use FilamentOAuth\FilamentOAuthPlugin;

class AdminPanelProvider extends PanelProvider
{
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
                    ->sso('nextcloud')  // Auto-redirect to Nextcloud
                    ->disableFallbackLogin()  // Disable local login
                    ->createUsers(false)  // Don't auto-create users
                    ->syncUsers(true)  // Sync profile data from Nextcloud
                    ->allowedEmailDomains(['company.com']),  // Restrict to company domain
            ]);
    }
}
```

### Multi-Panel Setup (Admin + Guest)

For admin panel with SSO and guest panel with login buttons:

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
                ->allowedEmailDomains(['company.com']),
        ]);
}

// GuestPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('guest')
        ->path('guest')
        ->authGuard('web')
        ->plugins([
            FilamentOAuthPlugin::make()
                ->nextcloud(
                    url: env('NEXTCLOUD_URL'),
                    clientId: env('NEXTCLOUD_GUEST_CLIENT_ID'),
                    clientSecret: env('NEXTCLOUD_GUEST_CLIENT_SECRET'),
                )
                ->buttons()  // Show login button
                ->allowFallbackLogin()  // Allow local login as fallback
                ->createUsers(true)  // Auto-create users from Nextcloud
                ->syncUsers(true),
        ]);
}
```

## Step 4: Verify Configuration

1. Visit your Filament admin panel: `https://your-app.local/admin`
2. If SSO is enabled, you should be redirected to Nextcloud login
3. After authenticating with Nextcloud, you should be logged in to your Filament panel
4. If SSO is disabled, you should see a "Login with Nextcloud" button

## Supported Nextcloud Versions

- Nextcloud 16.0+
- Supports OpenID Connect protocol
- Uses `.well-known/openid-configuration` for auto-discovery

## Security Considerations

### Email Verification

Nextcloud OIDC claims include `email_verified`. By default, unverified emails are accepted. To require verified emails:

```php
FilamentOAuthPlugin::make()
    ->nextcloud(...)
    ->requireVerifiedEmail()  // Only allow verified emails
```

### Email Domains

Restrict login to specific email domains:

```php
FilamentOAuthPlugin::make()
    ->nextcloud(...)
    ->allowedEmailDomains(['company.com', 'partner.com'])
```

### Account Linking

By default, users are linked by email. To disable this and require exact provider user ID match:

```php
config()->set('filament-oauth.defaults.link_by_email', false);
```

## Troubleshooting

### "Invalid redirect URI"

Ensure the redirect URI in your Nextcloud OAuth client configuration exactly matches the route:

- Correct: `https://your-app.local/filament-oauth/admin/callback/nextcloud`
- Incorrect: `https://your-app.local:8000/filament-oauth/admin/callback/nextcloud`
- Incorrect: `http://` instead of `https://`

### "User not found" error

If the user exists in Nextcloud but not in your Filament app:

1. Ensure `createUsers(true)` is enabled for that panel
2. Check email domain restrictions
3. Verify email verification requirements

### Users not syncing profile data

Ensure `syncUsers(true)` is enabled and the user has the necessary scopes in their OAuth token.

## User Data Mapping

Nextcloud OIDC claims are mapped as follows:

| Nextcloud Claim | Filament Field |
|-----------------|----------------|
| `sub` | Provider User ID |
| `email` | Email |
| `name` | Name |
| `picture` | Avatar |
| `email_verified` | Email Verified Flag |

## Environment Variables Reference

```env
# Required
NEXTCLOUD_URL=https://cloud.example.com
NEXTCLOUD_ADMIN_CLIENT_ID=xxxxx
NEXTCLOUD_ADMIN_CLIENT_SECRET=xxxxx

# Optional (for multiple panels)
NEXTCLOUD_GUEST_CLIENT_ID=xxxxx
NEXTCLOUD_GUEST_CLIENT_SECRET=xxxxx
```

## Additional Resources

- [Nextcloud OAuth Documentation](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/OCS/ocs-api-overview.html)
- [OpenID Connect Specification](https://openid.net/connect/)
- [Filament OAuth Configuration Guide](../README.md)

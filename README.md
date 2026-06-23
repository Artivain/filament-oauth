# Filament OAuth

Filament OAuth est un futur plugin open source pour ajouter l'authentification OAuth2, OpenID Connect et SSO aux panels Filament 5.

L'objectif du projet est de proposer une intégration simple pour les providers classiques comme Google ou GitHub, tout en supportant les environnements self-hosted et entreprise, notamment Nextcloud, avec un mode SSO seamless.

## Objectifs du projet

- Ajouter des connexions OAuth2 et OpenID Connect aux panels Filament 5.
- Supporter plusieurs panels Filament dans une même application.
- Configurer chaque panel directement dans son `PanelProvider`.
- Fournir un preset Nextcloud utilisable comme provider OAuth/OIDC.
- Permettre un mode SSO exclusif avec redirection automatique vers un fournisseur unique.
- Garder une architecture extensible pour Socialite, OIDC et providers personnalisés.
- Sécuriser la liaison des comptes via une table dédiée `oauth_accounts`.

## Principes d'architecture

### Configuration par `PanelProvider`

Le projet privilégie une configuration locale à chaque panel Filament.

Chaque panel doit pouvoir définir indépendamment :

- ses providers OAuth/OIDC ;
- son mode d'authentification ;
- son provider par défaut ;
- son guard ;
- ses règles de création utilisateur ;
- ses règles de synchronisation utilisateur ;
- ses règles de sécurité ;
- son comportement SSO ;
- son fallback vers la connexion locale.

Cette approche est préférée à une configuration globale unique, car elle respecte mieux le modèle multi-panel de Filament.

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

### Configuration globale minimale

Le fichier `config/filament-oauth.php` servira surtout à définir les valeurs par défaut et les options partagées.

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

Ordre de priorité prévu :

1. configuration explicite du plugin dans le `PanelProvider` ;
2. configuration spécifique éventuelle dans le fichier de config ;
3. valeurs par défaut globales ;
4. valeurs internes du package.

## Cas d'usage à supporter

### Login OAuth classique

Un panel peut afficher plusieurs boutons de connexion.

```php
FilamentOAuthPlugin::make()
    ->providers(['google', 'github'])
    ->buttons()
    ->allowFallbackLogin()
    ->createUsers(true);
```

### Nextcloud comme provider

Le plugin doit proposer un preset Nextcloud au-dessus du driver OIDC.

```php
FilamentOAuthPlugin::make()
    ->nextcloud(
        url: env('NEXTCLOUD_URL'),
        clientId: env('NEXTCLOUD_CLIENT_ID'),
        clientSecret: env('NEXTCLOUD_CLIENT_SECRET'),
    )
    ->buttons();
```

Variables d'environnement prévues :

```env
NEXTCLOUD_URL=https://cloud.example.com
NEXTCLOUD_CLIENT_ID=filament-admin
NEXTCLOUD_CLIENT_SECRET=secret
```

### Provider unique

Un panel peut être limité à un seul fournisseur.

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

### SSO seamless

Un panel peut rediriger automatiquement les utilisateurs non connectés vers le provider configuré.

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

Ce mode est destiné aux intégrations intranet, entreprise ou self-hosted, par exemple un panel Filament intégré à un écosystème Nextcloud.

### Multi-panels avec configurations différentes

Exemple cible avec un panel `admin` en SSO Nextcloud strict et un panel `guest` avec login social classique.

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

## Drivers prévus

### Driver Socialite

Pour les providers OAuth courants :

- Google ;
- GitHub ;
- GitLab ;
- Facebook ;
- Microsoft ;
- autres providers Socialite.

### Driver OIDC

Pour les providers OpenID Connect et environnements self-hosted :

- Nextcloud ;
- Keycloak ;
- Authentik ;
- Authelia ;
- Zitadel ;
- Azure AD / Entra ID ;
- Okta ;
- Auth0.

### Driver personnalisé

Le package devra permettre d'ajouter un provider custom sans modifier le coeur du plugin.

## Routes prévues

Les routes doivent inclure l'identifiant du panel pour éviter les collisions multi-panels.

```text
GET /filament-oauth/{panel}/redirect/{provider}
GET /filament-oauth/{panel}/callback/{provider}
GET /filament-oauth/{panel}/error
POST /filament-oauth/{panel}/logout
```

Exemples :

```text
GET /filament-oauth/admin/redirect/nextcloud
GET /filament-oauth/admin/callback/nextcloud
GET /filament-oauth/guest/redirect/github
GET /filament-oauth/guest/callback/github
```

## Contexte panel

Le coeur du package devra résoudre un contexte OAuth propre au panel courant.

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

Ce contexte sera utilisé pour :

- résoudre la configuration effective ;
- générer les redirect URIs ;
- valider qu'un provider est autorisé sur un panel ;
- connecter l'utilisateur avec le bon guard ;
- gérer le fallback login ;
- gérer les erreurs et redirections par panel.

## Stockage des comptes OAuth

Le projet utilisera une table dédiée `oauth_accounts`.

Champs envisagés :

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

Par défaut, les comptes seront probablement scopés par panel.

```php
'accounts' => [
    'scope_by_panel' => true,
],
```

Cela permet d'éviter les collisions si deux panels utilisent le même provider avec des règles différentes.

## Résolution utilisateur

Ordre de résolution recommandé :

1. résoudre le panel et le provider ;
2. vérifier que le provider est autorisé pour ce panel ;
3. récupérer le profil OAuth/OIDC ;
4. chercher un compte OAuth existant via `panel_id`, `provider` et `provider_user_id` ou `sub` ;
5. chercher par email si la liaison par email est autorisée ;
6. créer l'utilisateur si la création automatique est autorisée ;
7. synchroniser les attributs autorisés ;
8. connecter l'utilisateur avec le guard du panel ;
9. rediriger vers l'URL intended du panel.

## Sécurité

Options prévues :

```php
FilamentOAuthPlugin::make()
    ->requireVerifiedEmail()
    ->allowedEmailDomains(['company.com'])
    ->disableAccountLinkingByEmail()
    ->createUsers(false);
```

Le package devra gérer :

- validation du state OAuth ;
- nonce OIDC ;
- protection contre les boucles en mode SSO ;
- providers non autorisés par panel ;
- email manquant ;
- email non vérifié ;
- domaines email autorisés ;
- liaison de compte contrôlée ;
- erreurs de callback ;
- redirections sûres après login.

## Logout

Le logout devra être configurable par panel.

Stratégies envisagées :

- logout local uniquement ;
- logout local avec page intermédiaire ;
- logout fédéré OIDC si le provider le supporte.

Le logout local uniquement peut être problématique en mode SSO seamless, car l'utilisateur peut être immédiatement reconnecté par le provider. Le projet devra donc prévoir une stratégie dédiée pour éviter les boucles de reconnexion.

## Roadmap

### Phase 1 — Fondation package

- Initialiser le package Laravel.
- Créer le service provider.
- Créer le plugin Filament 5.
- Publier la configuration.
- Ajouter Laravel Pint, Pest/PHPUnit et analyse statique.
- Mettre en place GitHub Actions.

### Phase 2 — Architecture panel-aware

- Créer `OAuthPanelContext`.
- Créer une registry des panels OAuth.
- Résoudre la configuration effective par panel.
- Résoudre le guard depuis le panel Filament.
- Définir l'ordre de priorité des configurations.

### Phase 3 — Routes multi-panels

- Ajouter les routes avec `{panel}` et `{provider}`.
- Générer les redirect URIs par panel.
- Stocker le panel et le provider dans le state OAuth.
- Valider les callbacks par panel.
- Prévoir les routes d'erreur et de logout par panel.

### Phase 4 — Stockage et résolution utilisateur

- Ajouter la migration `oauth_accounts`.
- Ajouter l'option `scope_by_panel`.
- Implémenter la résolution par provider user id ou subject OIDC.
- Implémenter la liaison par email contrôlée.
- Implémenter la création utilisateur optionnelle.
- Implémenter la synchronisation utilisateur optionnelle.

### Phase 5 — Driver Socialite

- Ajouter le driver Socialite.
- Supporter Google et GitHub en premier.
- Afficher les boutons OAuth sur la page login Filament.
- Tester les callbacks et erreurs OAuth.

### Phase 6 — Driver OIDC

- Ajouter un driver OpenID Connect générique.
- Supporter la discovery `.well-known/openid-configuration`.
- Gérer authorization endpoint, token endpoint et userinfo endpoint.
- Mapper les claims OIDC vers le modèle utilisateur.
- Gérer `sub`, `email`, `email_verified`, `name` et `picture`.

### Phase 7 — Preset Nextcloud

- Ajouter un helper `nextcloud()`.
- Documenter la création d'un client OAuth/OIDC dans Nextcloud.
- Gérer les redirect URIs par panel.
- Tester Nextcloud avec endpoints mockés.
- Fournir un exemple de configuration admin/guest.

### Phase 8 — Modes login, provider unique et SSO

- Implémenter le mode `buttons`.
- Implémenter le mode `single_provider`.
- Implémenter le mode `sso` avec auto-redirection.
- Ajouter le fallback login configurable.
- Ajouter la protection anti-boucles.
- Gérer les erreurs par panel.

### Phase 9 — Sécurité avancée

- Ajouter l'allowlist de domaines email.
- Ajouter les règles email vérifié.
- Ajouter les règles de liaison par email.
- Ajouter des callbacks personnalisables.
- Ajouter les events d'authentification.

### Phase 10 — Documentation et release

- Rédiger l'installation complète.
- Documenter la configuration par `PanelProvider`.
- Documenter Nextcloud.
- Documenter le mode SSO seamless.
- Documenter les environnements multi-panels.
- Ajouter `CHANGELOG.md`.
- Ajouter `CONTRIBUTING.md`.
- Publier une première version beta.

## MVP recommandé

La première version utile devrait inclure :

1. plugin Filament 5 ;
2. configuration par `PanelProvider` ;
3. routes multi-panels ;
4. table `oauth_accounts` ;
5. driver Socialite avec Google et GitHub ;
6. driver OIDC minimal ;
7. preset Nextcloud ;
8. mode login avec boutons ;
9. mode provider unique ;
10. mode SSO seamless ;
11. fallback login configurable ;
12. tests multi-panels de base.

## Tests à prévoir

- Panel `admin` en SSO Nextcloud strict.
- Panel `guest` avec Google/GitHub et fallback login.
- Provider autorisé sur un panel mais refusé sur un autre.
- Guards différents par panel.
- Redirect URI différente par panel.
- Callback vers mauvais panel refusé.
- Utilisateur créé sur `guest` mais refusé sur `admin`.
- Liaison par email autorisée ou refusée selon panel.
- Protection contre les boucles SSO.
- Gestion d'un email OIDC non vérifié.

## Licence

Le projet est prévu comme package open source. La licence cible est GNU AGPL.

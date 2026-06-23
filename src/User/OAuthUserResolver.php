<?php

declare(strict_types=1);

namespace FilamentOAuth\User;

use FilamentOAuth\Models\OAuthAccount;
use FilamentOAuth\Panel\OAuthPanelContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

final class OAuthUserResolver
{
    public function resolve(OAuthPanelContext $context, SocialiteUser $oauthUser): Authenticatable
    {
        $account = $this->findAccount($context, (string) $oauthUser->getId());

        if ($account instanceof OAuthAccount) {
            $user = $account->user()->first();

            if ($user instanceof Authenticatable) {
                $this->syncAccount($account, $oauthUser);

                return $user;
            }
        }

        $user = $this->findUserByEmail($context, $oauthUser->getEmail())
            ?? $this->createUser($context, $oauthUser);

        if (! $user instanceof Authenticatable) {
            throw new RuntimeException('Unable to resolve an authenticatable user for the OAuth callback.');
        }

        $this->linkAccount($context, $user, $oauthUser);

        return $user;
    }

    private function findAccount(OAuthPanelContext $context, string $providerUserId): ?OAuthAccount
    {
        $query = OAuthAccount::query()
            ->where('provider', $context->provider())
            ->where('provider_user_id', $providerUserId);

        if (config('filament-oauth.accounts.scope_by_panel', true)) {
            $query->where('panel_id', $context->panelId());
        }

        return $query->first();
    }

    private function findUserByEmail(OAuthPanelContext $context, ?string $email): ?Authenticatable
    {
        if (! $email || ! ($context->configuration()['user']['link_by_email'] ?? true)) {
            return null;
        }

        $user = $this->userModel()::query()->where('email', $email)->first();

        return $user instanceof Authenticatable ? $user : null;
    }

    private function createUser(OAuthPanelContext $context, SocialiteUser $oauthUser): ?Authenticatable
    {
        if (! ($context->configuration()['user']['create_user'] ?? false)) {
            return null;
        }

        $userModel = $this->userModel();
        $user = new $userModel;

        if (! $user instanceof Authenticatable) {
            throw new RuntimeException('The configured user model must be an Eloquent model implementing Authenticatable.');
        }

        $user->forceFill([
            'name' => $oauthUser->getName() ?: $oauthUser->getNickname() ?: $oauthUser->getEmail(),
            'email' => $oauthUser->getEmail(),
            'password' => null,
        ])->save();

        return $user;
    }

    private function linkAccount(OAuthPanelContext $context, Authenticatable $user, SocialiteUser $oauthUser): OAuthAccount
    {
        return OAuthAccount::query()->create($this->accountAttributes($context, $user, $oauthUser));
    }

    private function syncAccount(OAuthAccount $account, SocialiteUser $oauthUser): void
    {
        $account->forceFill([
            'email' => $oauthUser->getEmail(),
            'name' => $oauthUser->getName(),
            'nickname' => $oauthUser->getNickname(),
            'avatar_url' => $oauthUser->getAvatar(),
            'access_token' => $this->attribute($oauthUser, 'token'),
            'refresh_token' => $this->attribute($oauthUser, 'refreshToken'),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function accountAttributes(OAuthPanelContext $context, Authenticatable $user, SocialiteUser $oauthUser): array
    {
        return [
            'user_id' => $user->getAuthIdentifier(),
            'panel_id' => config('filament-oauth.accounts.scope_by_panel', true) ? $context->panelId() : null,
            'provider' => $context->provider(),
            'provider_user_id' => (string) $oauthUser->getId(),
            'subject' => (string) $oauthUser->getId(),
            'email' => $oauthUser->getEmail(),
            'name' => $oauthUser->getName(),
            'nickname' => $oauthUser->getNickname(),
            'avatar_url' => $oauthUser->getAvatar(),
            'access_token' => $this->attribute($oauthUser, 'token'),
            'refresh_token' => $this->attribute($oauthUser, 'refreshToken'),
            'raw_user' => $this->attribute($oauthUser, 'user'),
        ];
    }

    private function attribute(SocialiteUser $oauthUser, string $attribute): mixed
    {
        return property_exists($oauthUser, $attribute) ? $oauthUser->{$attribute} : null;
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        return config('filament-oauth.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }
}

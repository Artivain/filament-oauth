<?php

declare(strict_types=1);

namespace FilamentOAuth\OIDC;

use FilamentOAuth\Panel\OAuthPanelContext;
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;

final class OIDCConfigurator
{
    public function redirect(OAuthPanelContext $context): mixed
    {
        $client = $this->client($context);
        
        return redirect($client->getAuthenticationUrl());
    }

    /**
     * @throws OpenIDConnectClientException
     */
    public function user(OAuthPanelContext $context): OIDCUser
    {
        $client = $this->client($context);
        $client->authenticate();

        $userInfo = $client->requestUserInfo();

        return new OIDCUser(
            id: $userInfo->sub ?? null,
            email: $userInfo->email ?? null,
            name: $userInfo->name ?? null,
            picture: $userInfo->picture ?? null,
            emailVerified: $userInfo->email_verified ?? false,
            raw: $userInfo,
        );
    }

    /**
     * @throws OpenIDConnectClientException
     */
    private function client(OAuthPanelContext $context): OpenIDConnectClient
    {
        $config = $context->providerConfig();

        $baseUrl = $config['base_url'] ?? null;
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;
        $redirect = $config['redirect_uri'] ?? route('filament-oauth.callback', [
            'panel' => $context->panelId(),
            'provider' => $context->provider(),
        ]);

        if (! $baseUrl || ! $clientId || ! $clientSecret) {
            throw new OpenIDConnectClientException('Missing required OIDC configuration');
        }

        $client = new OpenIDConnectClient($baseUrl, $clientId, $clientSecret);
        $client->setRedirectURL($redirect);

        // Support .well-known/openid-configuration discovery
        if ($config['discovery'] ?? true) {
            $client->providerConfigURL = rtrim($baseUrl, '/') . '/.well-known/openid-configuration';
        } else {
            $client->setProviderURL($baseUrl);
            $client->setAuthorizationEndpoint($config['authorization_endpoint'] ?? null);
            $client->setTokenEndpoint($config['token_endpoint'] ?? null);
            $client->setUserinfoEndpoint($config['userinfo_endpoint'] ?? null);
        }

        // Set scopes
        $scopes = $config['scopes'] ?? ['openid', 'profile', 'email'];
        if (is_array($scopes)) {
            $client->addScope($scopes);
        }

        // Optional: add PKCE support
        if ($config['pkce'] ?? false) {
            $client->setCodeChallengeMethod('S256');
        }

        return $client;
    }
}

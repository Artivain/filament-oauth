<?php

declare(strict_types=1);

return [
    'route_prefix' => 'filament-oauth',
    'route_middleware' => ['web'],

    'user_model' => null,

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

    'panels' => [
        // Optional panel-specific defaults may be declared here and are merged
        // before explicit PanelProvider plugin configuration.
    ],
];

<?php

declare(strict_types=1);

namespace FilamentOAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OAuthAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'email_verified' => 'boolean',
        'token_expires_at' => 'datetime',
        'raw_user' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo($this->userModel());
    }

    private function userModel(): string
    {
        return config('filament-oauth.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }
}

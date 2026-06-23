<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-oauth.accounts.table', 'oauth_accounts'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('panel_id')->nullable()->index();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('subject')->nullable();
            $table->string('email')->nullable()->index();
            $table->boolean('email_verified')->nullable();
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->text('avatar_url')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('raw_user')->nullable();
            $table->timestamps();

            $table->unique(['panel_id', 'provider', 'provider_user_id'], 'oauth_accounts_panel_provider_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-oauth.accounts.table', 'oauth_accounts'));
    }
};

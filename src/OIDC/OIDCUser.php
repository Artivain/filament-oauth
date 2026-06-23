<?php

declare(strict_types=1);

namespace FilamentOAuth\OIDC;

use stdClass;

final readonly class OIDCUser
{
    public function __construct(
        private ?string $id,
        private ?string $email,
        private ?string $name,
        private ?string $picture,
        private bool $emailVerified,
        private stdClass $raw,
    ) {}

    public function id(): ?string
    {
        return $this->id;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function picture(): ?string
    {
        return $this->picture;
    }

    public function emailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function raw(): stdClass
    {
        return $this->raw;
    }
}

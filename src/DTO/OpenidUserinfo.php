<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class OpenidUserinfo
{
    public function __construct(
        private readonly string $sub,
        #[SerializedName('email_verified')]
        private readonly bool $emailVerified,
        private readonly string $name,
        #[SerializedName('preferred_username')]
        private readonly string $preferredUsername,
        private readonly ?string $locale,
        #[SerializedName('given_name')]
        private readonly string $givenName,
        #[SerializedName('family_name')]
        private readonly string $familyName,
        private readonly string $email,
    ) {
    }

    public function getSub(): string
    {
        return $this->sub;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPreferredUsername(): string
    {
        return $this->preferredUsername;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

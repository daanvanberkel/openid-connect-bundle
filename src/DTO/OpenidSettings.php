<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle\DTO;

class OpenidSettings
{
    public function __construct(
        private readonly string $openIdConfigurationUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly string $scopes,
        private readonly int $openIdConfigurationTtl,
        private readonly string $audience,
    ) {
    }

    public function getOpenIdConfigurationUrl(): string
    {
        return $this->openIdConfigurationUrl;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getScopes(): string
    {
        return $this->scopes;
    }

    public function getOpenIdConfigurationTtl(): int
    {
        return $this->openIdConfigurationTtl;
    }

    public function getAudience(): string
    {
        return $this->audience;
    }
}

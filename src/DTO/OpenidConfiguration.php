<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class OpenidConfiguration
{
    public function __construct(
        #[SerializedName('authorization_endpoint')]
        private readonly string $authorizationEndpoint,
        #[SerializedName('token_endpoint')]
        private readonly string $tokenEndpoint,
        #[SerializedName('userinfo_endpoint')]
        private readonly string $userinfoEndpoint,
        #[SerializedName('jwks_uri')]
        private readonly string $jwksUri,
        #[SerializedName('end_session_endpoint')]
        private readonly string $endSessionEndpoint,
    ) {
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->authorizationEndpoint;
    }

    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }

    public function getUserinfoEndpoint(): string
    {
        return $this->userinfoEndpoint;
    }

    public function getJwksUri(): string
    {
        return $this->jwksUri;
    }

    public function getEndSessionEndpoint(): string
    {
        return $this->endSessionEndpoint;
    }
}

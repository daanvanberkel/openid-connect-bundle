<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class OpenidTokens
{
    public function __construct(
        #[SerializedName('access_token')]
        private readonly string $accessToken,
        #[SerializedName('refresh_token')]
        private readonly string $refreshToken,
        #[SerializedName('id_token')]
        private readonly string $idToken,
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getIdToken(): string
    {
        return $this->idToken;
    }
}

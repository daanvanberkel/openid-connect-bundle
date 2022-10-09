<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle;

use Symfony\Component\HttpFoundation\RequestStack;

class OpenidSessionManager
{
    private const STATE_SESSION_KEY = 'openid-state';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function setState(string $state): void
    {
        $this->requestStack->getSession()->set(self::STATE_SESSION_KEY, $state);
    }

    public function getState(): ?string
    {
        return $this->requestStack->getSession()->get(self::STATE_SESSION_KEY);
    }
}

<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle;

use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_key_exists;
use function base64_decode;
use function count;
use function explode;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class OpenidJwksService
{
    private const CACHE_JWKS_KEY = 'openid-jwks';

    public function __construct(
        private readonly OpenidConfigurationService $configurationService,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @throws OpenidException
     */
    public function getJwkForJwt(string $jwt): JWK
    {
        $kid = $this->getKidFromJwt($jwt);
        $jwks = $this->getJwks();

        if (!$jwks->has($kid)) {
            // Invalidate cache and try to fetch fresh keys
            $this->cache->delete(self::CACHE_JWKS_KEY);
            $jwks = $this->getJwks();
        }

        if (!$jwks->has($kid)) {
            throw new OpenidException('JWT signed with invalid key');
        }

        return $jwks->get($kid);
    }

    /**
     * @throws OpenidException
     */
    private function getKidFromJwt(string $jwt): string
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new OpenidException('JWT must consist of three parts');
        }

        $decoded = base64_decode($parts[0]);
        try {
            $data = json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new OpenidException($e->getMessage(), $e->getCode(), $e);
        }

        if (!array_key_exists('kid', $data)) {
            throw new OpenidException('JWT is missing a kid');
        }
        return $data['kid'];
    }

    /**
     * @throws JsonException
     * @throws OpenidException
     */
    private function getJwks(): JWKSet
    {
        return $this->cache->get(self::CACHE_JWKS_KEY, function (ItemInterface $item) {
            $item->expiresAfter(5 * 60);

            $jwksUrl = $this->configurationService->getConfiguration()->getJwksUri();
            $response = $this->httpClient->request('GET', $jwksUrl);
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new OpenidException(sprintf('Received %s from JWKS', $response->getStatusCode()));
            }
            return JWKSet::createFromJson($response->getContent());
        });
    }
}

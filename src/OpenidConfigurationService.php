<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle;

use Daanvanberkel\OpenidConnectBundle\DTO\OpenidConfiguration;
use Daanvanberkel\OpenidConnectBundle\DTO\OpenidSettings;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function sha1;
use function sprintf;

class OpenidConfigurationService
{
    public function __construct(
        private readonly OpenidSettings $settings,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function getConfiguration(): OpenidConfiguration
    {
        $cacheKey = sha1($this->settings->getOpenIdConfigurationUrl());
        return $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter($this->settings->getOpenIdConfigurationTtl());

            $response = $this->httpClient->request('GET', $this->settings->getOpenIdConfigurationUrl());
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new OpenidException(sprintf('Received %s for openid configuration', $response->getStatusCode()));
            }

            return $this->serializer->deserialize($response->getContent(), OpenidConfiguration::class, 'json');
        });
    }
}

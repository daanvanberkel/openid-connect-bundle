<?php

declare(strict_types=1);

namespace Daanvanberkel\OpenidConnectBundle;

use Daanvanberkel\OpenidConnectBundle\DTO\OpenidSettings;
use Daanvanberkel\OpenidConnectBundle\DTO\OpenidTokens;
use Daanvanberkel\OpenidConnectBundle\DTO\OpenidUserinfo;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\InvalidClaimException;
use Jose\Component\Checker\InvalidHeaderException;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function json_decode;
use function sprintf;
use function urlencode;

use const JSON_THROW_ON_ERROR;

class OpenidClient
{
    public function __construct(
        private readonly OpenidConfigurationService $configurationService,
        private readonly OpenidSessionManager $sessionManager,
        private readonly OpenidSettings $settings,
        private readonly OpenidJwksService $openidJwksService,
        private readonly HttpClientInterface $httpClient,
        private readonly SerializerInterface $serializer,
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    public function getAuthorizationUrl(): string
    {
        $config = $this->configurationService->getConfiguration();
        $state = Uuid::v4()->toRfc4122();
        $this->sessionManager->setState($state);

        return sprintf(
            '%s?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&state=%s',
            $config->getAuthorizationEndpoint(),
            $this->settings->getClientId(),
            urlencode($this->settings->getRedirectUri()),
            $this->settings->getScopes(),
            $state,
        );
    }

    public function getLogoutUrl(): string
    {
        return $this->configurationService->getConfiguration()->getEndSessionEndpoint();
    }

    public function getTokensFromCode(string $code, string $state): OpenidTokens
    {
        if ($state !== $this->sessionManager->getState()) {
            throw new OpenidException('State mismatch');
        }

        $url = $this->configurationService->getConfiguration()->getTokenEndpoint();
        $response = $this->httpClient->request('POST', $url, [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->settings->getRedirectUri(),
                'client_id' => $this->settings->getClientId(),
                'client_secret' => $this->settings->getClientSecret(),
            ],
            'max_redirects' => 0,
        ]);
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new OpenidException(sprintf('Received %s from code exchange', $response->getStatusCode()));
        }
        return $this->serializer->deserialize($response->getContent(), OpenidTokens::class, 'json');
    }

    /**
     * @throws ExceptionInterface
     * @throws OpenidException
     */
    public function getUserInfo(string $idToken): OpenidUserinfo
    {
        $jws = $this->verifyToken($idToken);
        $this->verifyHeaders($jws);
        $claims = $this->verifyClaims($jws);
        return $this->denormalizer->denormalize($claims, OpenidUserinfo::class);
    }

    /**
     * @throws OpenidException
     */
    private function verifyToken(string $token): JWS
    {
        $jwk = $this->openidJwksService->getJwkForJwt($token);
        $algorithmManager = new AlgorithmManager([new RS256()]);
        $jwsVerifier = new JWSVerifier($algorithmManager);
        $serializerManager = new JWSSerializerManager([new CompactSerializer()]);
        $jws = $serializerManager->unserialize($token);
        $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);
        if (!$isVerified) {
            throw new OpenidException('Signature for given JWT cannot be verified');
        }
        return $jws;
    }

    /**
     * @throws OpenidException
     */
    private function verifyHeaders(JWS $jws): void
    {
        $headerCheckerManager = new HeaderCheckerManager([
            new AlgorithmChecker(['RS256']),
        ], [
            new JWSTokenSupport(),
        ]);

        try {
            $headerCheckerManager->check($jws, 0);
        } catch (InvalidHeaderException $e) {
            throw new OpenidException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @return array<string, mixed>
     * @throws OpenidException
     */
    private function verifyClaims(JWS $jws): array
    {
        $claimCheckerManager = new ClaimCheckerManager([
            new ExpirationTimeChecker(),
            new IssuedAtChecker(),
            new NotBeforeChecker(),
            new AudienceChecker($this->settings->getAudience()),
        ]);

        try {
            $payload = $jws->getPayload();
            if ($payload === null) {
                throw new OpenidException('Missing token payload');
            }
            $claims = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
            $claimCheckerManager->check($claims);
        } catch (InvalidClaimException | JsonException $e) {
            throw new OpenidException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $claims;
    }
}

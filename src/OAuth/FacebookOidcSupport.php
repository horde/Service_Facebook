<?php

declare(strict_types=1);

/**
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/bsd BSD
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Service\Facebook\OAuth;

use Horde\Jwt\Key\Jwk;
use Horde\Jwt\TokenDecoder;
use Horde\Jwt\Token\VerifiedToken;
use Horde\Jwt\Verifier\Rs256Verifier;
use Horde\Service\Facebook\FacebookApiException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Verifies Facebook Login id_token JWTs.
 *
 * When a caller runs the "openid" scope through the Facebook Login
 * OAuth2 flow, Meta returns an id_token alongside the access_token.
 * The id_token is a signed JWT whose claims identify the authenticated
 * user in a way the caller can verify offline (aud, sub, exp, iss,
 * iat, plus nonce round-tripping). Meta signs with RS256 and rotates
 * its keys periodically, publishing the current key set at
 * FacebookProviderConfig::JWKS_URI.
 *
 * This helper:
 *   1. Fetches the JWKS document from Meta via the supplied PSR-18
 *      client and PSR-17 request factory.
 *   2. Extracts the token's `kid` header parameter.
 *   3. Locates the matching JWK entry and converts it to a
 *      Horde\Jwt\Key\PublicKey via Jwk::toPublicKey.
 *   4. Builds an Rs256Verifier over that key.
 *   5. Delegates to Horde\Jwt\TokenDecoder to run the actual
 *      signature + claims verification.
 *
 * Callers are expected to pass the appropriate expected issuer
 * (FacebookProviderConfig::ISSUER) and audience (the app's Facebook
 * app_id) via the options array. Nonce verification is left to the
 * caller because it involves state the caller stored between the
 * authorize redirect and the callback; this helper does not track
 * per-request state.
 *
 * The JWKS fetch happens on every call by default. Callers who make
 * frequent verification calls should wrap the supplied PSR-18 client
 * with an HTTP cache (Horde\Http supports this via response headers,
 * as does Guzzle with a cache middleware). This helper deliberately
 * does not embed its own cache — cache policy is the framework's
 * concern, not the library's.
 *
 * FUTURE: the JWKS-fetching + kid-selection + alg-to-verifier logic
 * here is not Facebook-specific and is scheduled for extraction into
 * a standalone horde/jwks package once a second concrete consumer
 * appears (Google Sign-In, webhook signature verification, OidcServer
 * relying-party helpers). At that point this class collapses to a
 * thin wrapper binding Meta's JWKS URI to the shared JwksClient. See
 * ~/php/horde-development/libraries/jwks/jwks-standalone-extraction-plan-2026-07-11.md
 * for the extraction design and the trigger conditions.
 */
final class FacebookOidcSupport
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly TokenDecoder $decoder = new TokenDecoder(),
        private readonly string $jwksUri = FacebookProviderConfig::JWKS_URI,
    ) {}

    /**
     * Verify a Meta-issued OIDC id_token and return the decoded claims.
     *
     * $options is forwarded to Horde\Jwt\TokenDecoder::decode; callers
     * should at minimum set verify_iss to FacebookProviderConfig::ISSUER
     * and verify_aud to their Facebook app_id.
     *
     * @param array<string, mixed> $options
     */
    public function verifyIdToken(string $idToken, array $options = []): VerifiedToken
    {
        $header = $this->decodeHeader($idToken);
        $kid = $header['kid'] ?? null;
        if (!is_string($kid) || $kid === '') {
            throw new FacebookApiException('id_token has no kid header; cannot select verification key');
        }

        $jwk = $this->fetchJwkByKid($kid);
        $publicKey = Jwk::toPublicKey($jwk);
        $verifier = new Rs256Verifier($publicKey);

        return $this->decoder->decode($idToken, $verifier, $options);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeHeader(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new FacebookApiException('id_token has malformed structure; expected three dot-separated segments');
        }

        $decoded = base64_decode(strtr($parts[0], '-_', '+/'), true);
        if ($decoded === false) {
            throw new FacebookApiException('id_token header segment is not valid base64url');
        }

        $header = json_decode($decoded, true);
        if (!is_array($header)) {
            throw new FacebookApiException('id_token header segment is not a JSON object');
        }

        return $header;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJwkByKid(string $kid): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->jwksUri)
            ->withHeader('Accept', 'application/json');
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new FacebookApiException(
                sprintf(
                    'Failed to fetch Meta JWKS from %s: HTTP %d %s',
                    $this->jwksUri,
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                ),
                $response->getStatusCode(),
            );
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['keys']) || !is_array($decoded['keys'])) {
            throw new FacebookApiException('Meta JWKS response has no "keys" array');
        }

        foreach ($decoded['keys'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (($entry['kid'] ?? null) === $kid) {
                return $entry;
            }
        }

        throw new FacebookApiException(
            sprintf('Meta JWKS does not contain a key with kid "%s"', $kid),
        );
    }
}

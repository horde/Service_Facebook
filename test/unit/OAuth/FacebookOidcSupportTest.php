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

namespace Horde\Service\Facebook\Test\OAuth;

use Horde\Http\Client\Mock as MockHttpClient;
use Horde\Http\RequestFactory;
use Horde\Http\Response;
use Horde\Http\StreamFactory;
use Horde\Jwt\Base64Url;
use Horde\Jwt\Key\Jwk;
use Horde\Jwt\Key\PrivateKey;
use Horde\Jwt\Key\PublicKey;
use Horde\Jwt\Signer\Rs256Signer;
use Horde\Service\Facebook\FacebookApiException;
use Horde\Service\Facebook\OAuth\FacebookOidcSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end verification of a Meta-shaped id_token flow.
 *
 * Each test generates its own RSA keypair, constructs a signed JWT
 * carrying a specific `kid` header, publishes a JWKS document over
 * the mock HTTP client with a matching entry, and asserts that
 * FacebookOidcSupport walks the whole fetch → select → verify chain
 * correctly. This exercises the JWKS-fetching logic scheduled for
 * extraction into horde/jwks (see the plan doc referenced in the
 * class docblock); the tests move to that package's suite when the
 * extraction happens.
 */
#[CoversClass(FacebookOidcSupport::class)]
final class FacebookOidcSupportTest extends TestCase
{
    private const APP_ID = '111';
    private const USER_ID = '222';
    private const ISSUER = 'https://www.facebook.com';

    public function testVerifyIdTokenReturnsClaimsForWellFormedToken(): void
    {
        [$privateKey, $publicKey] = $this->generateKeyPair();
        $kid = 'fb-key-1';
        $mock = $this->mockPublishingJwks([$this->jwkEntry($publicKey, $kid)]);

        $helper = new FacebookOidcSupport($mock, new RequestFactory());
        $token = $this->signIdToken($privateKey, $kid, [
            'iss' => self::ISSUER,
            'aud' => self::APP_ID,
            'sub' => self::USER_ID,
            'iat' => time(),
            'exp' => time() + 3600,
        ]);

        $verified = $helper->verifyIdToken($token, [
            'verify_iss' => self::ISSUER,
            'verify_aud' => self::APP_ID,
        ]);

        $this->assertSame(self::USER_ID, $verified->getSubject());
        $this->assertSame(self::ISSUER, $verified->getIssuer());
        $this->assertSame(self::APP_ID, $verified->getAudience());
    }

    public function testVerifyIdTokenSelectsCorrectKeyByKid(): void
    {
        [$rightPrivate, $rightPublic] = $this->generateKeyPair();
        [, $otherPublic] = $this->generateKeyPair();

        $mock = $this->mockPublishingJwks([
            $this->jwkEntry($otherPublic, 'other-key'),
            $this->jwkEntry($rightPublic, 'the-right-key'),
        ]);

        $helper = new FacebookOidcSupport($mock, new RequestFactory());
        $token = $this->signIdToken($rightPrivate, 'the-right-key', [
            'iss' => self::ISSUER,
            'aud' => self::APP_ID,
            'sub' => self::USER_ID,
        ]);

        $verified = $helper->verifyIdToken($token, [
            'verify_iss' => self::ISSUER,
            'verify_aud' => self::APP_ID,
        ]);
        $this->assertSame(self::USER_ID, $verified->getSubject());
    }

    public function testRejectsTokenWithMissingKid(): void
    {
        [$privateKey, $publicKey] = $this->generateKeyPair();
        $mock = $this->mockPublishingJwks([$this->jwkEntry($publicKey, 'key-1')]);
        $helper = new FacebookOidcSupport($mock, new RequestFactory());

        $token = $this->signIdToken($privateKey, null, [
            'iss' => self::ISSUER,
            'aud' => self::APP_ID,
            'sub' => self::USER_ID,
        ]);

        $this->expectException(FacebookApiException::class);
        $this->expectExceptionMessage('no kid');
        $helper->verifyIdToken($token);
    }

    public function testRejectsTokenWhenKidNotInJwks(): void
    {
        [$privateKey, $publicKey] = $this->generateKeyPair();
        $mock = $this->mockPublishingJwks([$this->jwkEntry($publicKey, 'a-different-key')]);
        $helper = new FacebookOidcSupport($mock, new RequestFactory());

        $token = $this->signIdToken($privateKey, 'unknown-kid', [
            'iss' => self::ISSUER,
            'aud' => self::APP_ID,
            'sub' => self::USER_ID,
        ]);

        $this->expectException(FacebookApiException::class);
        $this->expectExceptionMessage('does not contain a key with kid "unknown-kid"');
        $helper->verifyIdToken($token);
    }

    public function testRaisesWhenJwksHttpFails(): void
    {
        $mock = new MockHttpClient();
        $mock->setResponse(new Response(500, [], (new StreamFactory())->createStream('server error')));
        $helper = new FacebookOidcSupport($mock, new RequestFactory());

        $this->expectException(FacebookApiException::class);
        $this->expectExceptionMessage('Failed to fetch Meta JWKS');
        // Any well-formed-looking header will trip the fetch attempt.
        $header = Base64Url::encode('{"alg":"RS256","kid":"whatever"}');
        $payload = Base64Url::encode('{"sub":"x"}');
        $helper->verifyIdToken("{$header}.{$payload}.stub");
    }

    public function testRaisesOnMalformedTokenStructure(): void
    {
        $helper = new FacebookOidcSupport(new MockHttpClient(), new RequestFactory());

        $this->expectException(FacebookApiException::class);
        $this->expectExceptionMessage('malformed structure');
        $helper->verifyIdToken('not-a-jwt');
    }

    /**
     * @return array{PrivateKey, PublicKey}
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privatePem);
        $details = openssl_pkey_get_details($resource);
        $publicPem = $details['key'];

        return [PrivateKey::fromString($privatePem), PublicKey::fromString($publicPem)];
    }

    /**
     * Build a JWK dictionary suitable for inclusion in a JWKS "keys"
     * array. Uses horde/jwt's Jwk::fromPublicKey to shape the RSA
     * modulus/exponent correctly, then adds the kid the caller wants.
     *
     * @return array<string, mixed>
     */
    private function jwkEntry(PublicKey $publicKey, string $kid): array
    {
        $jwk = Jwk::fromPublicKey($publicKey);
        $jwk['kid'] = $kid;
        $jwk['use'] = 'sig';
        $jwk['alg'] = 'RS256';
        return $jwk;
    }

    /**
     * Sign an id_token with the supplied private key. If $kid is
     * null, the header omits it (used to test the missing-kid
     * rejection path). Fully hand-assembled because horde/jwt's
     * TokenEncoder does not currently emit a `kid` header parameter.
     *
     * @param array<string, mixed> $claims
     */
    private function signIdToken(PrivateKey $privateKey, ?string $kid, array $claims): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        if ($kid !== null) {
            $header['kid'] = $kid;
        }

        $headerEncoded = Base64Url::encode(json_encode($header, JSON_THROW_ON_ERROR));
        $payloadEncoded = Base64Url::encode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signingInput = "{$headerEncoded}.{$payloadEncoded}";

        $signer = new Rs256Signer($privateKey);
        $signature = Base64Url::encode($signer->sign($signingInput));

        return "{$signingInput}.{$signature}";
    }

    /**
     * @param list<array<string, mixed>> $keys
     */
    private function mockPublishingJwks(array $keys): MockHttpClient
    {
        $mock = new MockHttpClient();
        $body = json_encode(['keys' => $keys], JSON_THROW_ON_ERROR);
        $mock->setResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            (new StreamFactory())->createStream($body),
        ));
        return $mock;
    }
}

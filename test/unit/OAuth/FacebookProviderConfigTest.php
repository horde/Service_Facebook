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

use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\OAuth\FacebookProviderConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FacebookProviderConfig::class)]
final class FacebookProviderConfigTest extends TestCase
{
    public function testV25ProducesVersionedEndpoints(): void
    {
        $config = FacebookProviderConfig::forVersion(GraphApiVersion::V25_0);

        $this->assertSame('https://www.facebook.com', $config->issuer);
        $this->assertSame('https://www.facebook.com/v25.0/dialog/oauth', $config->authorizationEndpoint);
        $this->assertSame('https://graph.facebook.com/v25.0/oauth/access_token', $config->tokenEndpoint);
        $this->assertSame('https://graph.facebook.com/v25.0/me/permissions', $config->revocationEndpoint);
        $this->assertSame('https://graph.facebook.com/v25.0/debug_token', $config->introspectionEndpoint);
    }

    public function testJwksUriIsVersionNeutral(): void
    {
        $v25 = FacebookProviderConfig::forVersion(GraphApiVersion::V25_0);
        $v22 = FacebookProviderConfig::forVersion(GraphApiVersion::V22_0);

        $this->assertSame($v25->jwksUri, $v22->jwksUri);
        $this->assertSame(FacebookProviderConfig::JWKS_URI, $v25->jwksUri);
    }

    /**
     * @return iterable<string, array{GraphApiVersion}>
     */
    public static function everyVersion(): iterable
    {
        foreach (GraphApiVersion::cases() as $case) {
            yield $case->value => [$case];
        }
    }

    #[DataProvider('everyVersion')]
    public function testEveryVersionProducesConsistentSegment(GraphApiVersion $version): void
    {
        $config = FacebookProviderConfig::forVersion($version);
        $segment = '/' . $version->urlSegment() . '/';

        $this->assertStringContainsString($segment, $config->authorizationEndpoint);
        $this->assertStringContainsString($segment, $config->tokenEndpoint);
        $this->assertStringContainsString($segment, $config->revocationEndpoint);
        $this->assertStringContainsString($segment, $config->introspectionEndpoint);
    }

    public function testAdvertisesRs256AsIdTokenSigningAlgorithm(): void
    {
        $config = FacebookProviderConfig::forVersion(GraphApiVersion::V25_0);
        $this->assertContains('RS256', $config->idTokenSigningAlgValuesSupported);
    }

    public function testCommonScopesIncludeOpenidAndPublicProfile(): void
    {
        $this->assertContains('openid', FacebookProviderConfig::COMMON_SCOPES);
        $this->assertContains('public_profile', FacebookProviderConfig::COMMON_SCOPES);
    }
}

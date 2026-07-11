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

namespace Horde\Service\Facebook\Test\Graph\Endpoint\V25;

use Horde\Http\Client\Mock as MockHttpClient;
use Horde\Http\RequestFactory;
use Horde\Http\Response;
use Horde\Http\StreamFactory;
use Horde\Service\Facebook\Graph\Endpoint\V25\DebugTokenEndpoint;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DebugTokenEndpoint::class)]
final class DebugTokenEndpointTest extends TestCase
{
    public function testGetUnwrapsDataEnvelopeAndHydrates(): void
    {
        $body = '{"data":{"app_id":"111","user_id":"222","is_valid":true,"expires_at":1800000000,"scopes":["public_profile","email"]}}';
        $mock = $this->mockWithBody(200, $body);
        $endpoint = $this->makeEndpoint($mock);

        $info = $endpoint->get('input-token', 'app-token');

        $this->assertSame('111', $info->appId());
        $this->assertSame('222', $info->userId());
        $this->assertTrue($info->isValid());
        $this->assertNotNull($info->expiresAt());
        $this->assertSame(['public_profile', 'email'], $info->scopes);
    }

    public function testGetSendsBothTokensAsQueryParams(): void
    {
        $mock = $this->mockWithBody(200, '{"data":{"app_id":"111","is_valid":true}}');
        $endpoint = $this->makeEndpoint($mock);

        $endpoint->get('input-token-value', 'app-token-value');

        $uri = (string) $mock->getRequests()[0]->getUri();
        $this->assertStringContainsString('/v25.0/debug_token', $uri);
        $this->assertStringContainsString('input_token=input-token-value', $uri);
        $this->assertStringContainsString('access_token=app-token-value', $uri);
    }

    private function makeEndpoint(MockHttpClient $client): DebugTokenEndpoint
    {
        return new DebugTokenEndpoint(
            $client,
            new RequestFactory(),
            new GraphUrlBuilder('https://graph.facebook.com', GraphApiVersion::V25_0),
            new GraphResponseDecoder(),
        );
    }

    private function mockWithBody(int $status, string $body): MockHttpClient
    {
        $mock = new MockHttpClient();
        $mock->setResponse(new Response(
            $status,
            ['Content-Type' => 'application/json'],
            (new StreamFactory())->createStream($body),
        ));
        return $mock;
    }
}

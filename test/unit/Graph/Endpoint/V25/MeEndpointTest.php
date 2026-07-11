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
use Horde\Service\Facebook\Graph\Endpoint\V25\MeEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\User;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\GraphErrorException;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MeEndpoint::class)]
final class MeEndpointTest extends TestCase
{
    public function testGetReturnsHydratedUser(): void
    {
        $mock = $this->mockWithBody(200, '{"id":"42","name":"Ada","email":"ada@example.org"}');
        $endpoint = $this->makeEndpoint($mock);

        $user = $endpoint->get(['id', 'name', 'email']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('42', $user->id);
        $this->assertSame('Ada', $user->name);
        $this->assertSame('ada@example.org', $user->email);
    }

    public function testGetSendsVersionedUrlWithFieldsParameter(): void
    {
        $mock = $this->mockWithBody(200, '{"id":"42","name":"Ada"}');
        $endpoint = $this->makeEndpoint($mock);

        $endpoint->get(['id', 'name', 'email']);

        $uri = (string) $mock->getRequests()[0]->getUri();
        $this->assertStringContainsString('/v25.0/me', $uri);
        $this->assertStringContainsString('fields=id%2Cname%2Cemail', $uri);
    }

    public function testGetOmitsFieldsWhenNoFieldsRequested(): void
    {
        $mock = $this->mockWithBody(200, '{"id":"42","name":"Ada"}');
        $endpoint = $this->makeEndpoint($mock);

        $endpoint->get();

        $uri = (string) $mock->getRequests()[0]->getUri();
        $this->assertStringNotContainsString('fields=', $uri);
    }

    public function testGetPropagatesGraphErrorException(): void
    {
        $body = '{"error":{"message":"Invalid token","type":"OAuthException","code":190,"error_subcode":463}}';
        $mock = $this->mockWithBody(401, $body);
        $endpoint = $this->makeEndpoint($mock);

        $this->expectException(GraphErrorException::class);
        $endpoint->get();
    }

    private function makeEndpoint(MockHttpClient $client): MeEndpoint
    {
        return new MeEndpoint(
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

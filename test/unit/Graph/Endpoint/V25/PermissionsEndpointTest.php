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
use Horde\Service\Facebook\Graph\Endpoint\V25\PermissionsEndpoint;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PermissionsEndpoint::class)]
final class PermissionsEndpointTest extends TestCase
{
    public function testListMyPermissionsReturnsHydratedList(): void
    {
        $body = '{"data":['
            . '{"permission":"public_profile","status":"granted"},'
            . '{"permission":"email","status":"granted"},'
            . '{"permission":"user_events","status":"declined"}'
            . ']}';
        $mock = $this->mockWithBody(200, $body);
        $endpoint = $this->makeEndpoint($mock);

        $permissions = $endpoint->listMyPermissions();

        $this->assertCount(3, $permissions);
        $this->assertSame('public_profile', $permissions[0]->name());
        $this->assertTrue($permissions[0]->isGranted());
        $this->assertTrue($permissions[1]->isGranted());
        $this->assertFalse($permissions[2]->isGranted());
    }

    public function testRevokeSendsDeleteToPermissionUrl(): void
    {
        $mock = $this->mockWithBody(200, '{"success":true}');
        $endpoint = $this->makeEndpoint($mock);

        $endpoint->revoke('user_events');

        $request = $mock->getRequests()[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertStringContainsString(
            '/v25.0/me/permissions/user_events',
            (string) $request->getUri(),
        );
    }

    public function testRevokeUrlEncodesPermissionName(): void
    {
        // Meta permission names are underscore-lowercase, but the URL
        // builder must still call rawurlencode so a caller who passes
        // something odd does not silently produce a malformed request.
        $mock = $this->mockWithBody(200, '{"success":true}');
        $endpoint = $this->makeEndpoint($mock);

        $endpoint->revoke('a b/c');

        $uri = (string) $mock->getRequests()[0]->getUri();
        $this->assertStringContainsString('/v25.0/me/permissions/a%20b%2Fc', $uri);
    }

    private function makeEndpoint(MockHttpClient $client): PermissionsEndpoint
    {
        return new PermissionsEndpoint(
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

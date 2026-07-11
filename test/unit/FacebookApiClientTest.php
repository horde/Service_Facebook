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

namespace Horde\Service\Facebook\Test;

use Horde\Http\Client\Mock as MockHttpClient;
use Horde\Http\RequestFactory;
use Horde\Http\Response;
use Horde\Http\StreamFactory;
use Horde\Service\Facebook\FacebookApiClient;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\DebugTokenInfo;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Event;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Permission;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\User;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\GraphErrorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FacebookApiClient::class)]
final class FacebookApiClientTest extends TestCase
{
    public function testCreateDefaultsToHighestSupportedVersion(): void
    {
        $client = FacebookApiClient::create(new MockHttpClient(), new RequestFactory());
        $this->assertSame(GraphApiVersion::default(), $client->version());
    }

    public function testCreateHonoursExplicitVersion(): void
    {
        $client = FacebookApiClient::create(
            new MockHttpClient(),
            new RequestFactory(),
            version: GraphApiVersion::V23_0,
        );
        $this->assertSame(GraphApiVersion::V23_0, $client->version());
    }

    public function testWithVersionReturnsNewImmutableInstance(): void
    {
        $original = FacebookApiClient::create(new MockHttpClient(), new RequestFactory());
        $pinned = $original->withVersion(GraphApiVersion::V22_0);

        $this->assertNotSame($original, $pinned);
        $this->assertSame(GraphApiVersion::default(), $original->version());
        $this->assertSame(GraphApiVersion::V22_0, $pinned->version());
    }

    public function testGetMeDelegatesThroughResolver(): void
    {
        $mock = $this->mockWithBody(200, '{"id":"42","name":"Ada"}');
        $client = FacebookApiClient::create($mock, new RequestFactory());

        $user = $client->getMe();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('42', $user->id);
        $this->assertStringContainsString('/v25.0/me', (string) $mock->getRequests()[0]->getUri());
    }

    public function testPinnedVersionAffectsUrlSegment(): void
    {
        $mock = $this->mockWithBody(200, '{"id":"42","name":"Ada"}');
        $client = FacebookApiClient::create($mock, new RequestFactory())
            ->withVersion(GraphApiVersion::V23_0);

        $client->getMe();

        $this->assertStringContainsString('/v23.0/me', (string) $mock->getRequests()[0]->getUri());
    }

    public function testListMyUpcomingEventsReturnsIteratorOverEvents(): void
    {
        $body = '{"data":[{"id":"1","name":"A","start_time":"2026-08-01T10:00:00+0000"}]}';
        $mock = $this->mockWithBody(200, $body);
        $client = FacebookApiClient::create($mock, new RequestFactory());

        $events = iterator_to_array($client->listMyUpcomingEvents());

        $this->assertCount(1, $events);
        $this->assertInstanceOf(Event::class, $events[0]);
        $this->assertStringContainsString('/v25.0/me/events', (string) $mock->getRequests()[0]->getUri());
    }

    public function testListMyPermissionsReturnsArrayOfPermissions(): void
    {
        $body = '{"data":[{"permission":"public_profile","status":"granted"}]}';
        $mock = $this->mockWithBody(200, $body);
        $client = FacebookApiClient::create($mock, new RequestFactory());

        $permissions = $client->listMyPermissions();

        $this->assertCount(1, $permissions);
        $this->assertInstanceOf(Permission::class, $permissions[0]);
        $this->assertTrue($permissions[0]->isGranted());
    }

    public function testRevokePermissionSendsDelete(): void
    {
        $mock = $this->mockWithBody(200, '{"success":true}');
        $client = FacebookApiClient::create($mock, new RequestFactory());

        $client->revokePermission('user_events');

        $request = $mock->getRequests()[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertStringContainsString(
            '/v25.0/me/permissions/user_events',
            (string) $request->getUri(),
        );
    }

    public function testDebugTokenUnwrapsAndReturnsTypedInfo(): void
    {
        $body = '{"data":{"app_id":"111","user_id":"222","is_valid":true}}';
        $mock = $this->mockWithBody(200, $body);
        $client = FacebookApiClient::create($mock, new RequestFactory());

        $info = $client->debugToken('input-token', 'app-token');

        $this->assertInstanceOf(DebugTokenInfo::class, $info);
        $this->assertSame('111', $info->appId());
        $this->assertTrue($info->isValid());
        $this->assertStringContainsString('/v25.0/debug_token', (string) $mock->getRequests()[0]->getUri());
    }

    public function testGraphErrorPropagatesFromFacade(): void
    {
        $body = '{"error":{"message":"Invalid token","type":"OAuthException","code":190,"error_subcode":463}}';
        $mock = $this->mockWithBody(401, $body);
        $client = FacebookApiClient::create($mock, new RequestFactory());

        try {
            $client->getMe();
            $this->fail('expected GraphErrorException');
        } catch (GraphErrorException $e) {
            $this->assertTrue($e->error()->isTokenExpired());
        }
    }

    public function testOriginalAndPinnedClientHitDifferentUrls(): void
    {
        $mock = $this->mockWithBody(200, '{"id":"42","name":"Ada"}');
        $mock->setResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            (new StreamFactory())->createStream('{"id":"42","name":"Ada"}'),
        ));

        $default = FacebookApiClient::create($mock, new RequestFactory());
        $pinned = $default->withVersion(GraphApiVersion::V22_0);

        // Provide two responses so we can drive both clients.
        $mock->setResponse([
            new Response(200, [], (new StreamFactory())->createStream('{"id":"42","name":"Ada"}')),
            new Response(200, [], (new StreamFactory())->createStream('{"id":"42","name":"Ada"}')),
        ]);

        $default->getMe();
        $pinned->getMe();

        $uris = array_map(static fn($r) => (string) $r->getUri(), $mock->getRequests());
        $this->assertStringContainsString('/v25.0/me', $uris[0]);
        $this->assertStringContainsString('/v22.0/me', $uris[1]);
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

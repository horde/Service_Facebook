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
use Horde\Service\Facebook\Graph\Endpoint\V25\EventsEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Event;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Horde\Service\Facebook\Graph\Pagination\Cursor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventsEndpoint::class)]
final class EventsEndpointTest extends TestCase
{
    public function testListMyEventsHydratesSinglePage(): void
    {
        $body = '{"data":['
            . '{"id":"1","name":"A","start_time":"2026-08-01T10:00:00+0000"},'
            . '{"id":"2","name":"B","start_time":"2026-08-02T10:00:00+0000"}'
            . ']}';
        $mock = $this->mockWithBody(200, $body);
        $endpoint = $this->makeEndpoint($mock);

        $events = iterator_to_array($endpoint->listMyEvents());

        $this->assertCount(2, $events);
        $this->assertInstanceOf(Event::class, $events[0]);
        $this->assertSame('A', $events[0]->name);
        $this->assertSame('B', $events[1]->name);
    }

    public function testListMyEventsWithCursorSendsCursorParams(): void
    {
        $mock = $this->mockWithBody(200, '{"data":[]}');
        $endpoint = $this->makeEndpoint($mock);

        iterator_to_array($endpoint->listMyEvents(new Cursor(after: 'abc123', limit: 25)));

        $uri = (string) $mock->getRequests()[0]->getUri();
        $this->assertStringContainsString('/v25.0/me/events', $uri);
        $this->assertStringContainsString('after=abc123', $uri);
        $this->assertStringContainsString('limit=25', $uri);
    }

    public function testListMyEventsAcceptsFieldsProjection(): void
    {
        $mock = $this->mockWithBody(200, '{"data":[]}');
        $endpoint = $this->makeEndpoint($mock);

        iterator_to_array($endpoint->listMyEvents(null, ['id', 'name', 'start_time', 'end_time', 'place']));

        $uri = (string) $mock->getRequests()[0]->getUri();
        $this->assertStringContainsString('fields=id%2Cname%2Cstart_time%2Cend_time%2Cplace', $uri);
    }

    private function makeEndpoint(MockHttpClient $client): EventsEndpoint
    {
        return new EventsEndpoint(
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

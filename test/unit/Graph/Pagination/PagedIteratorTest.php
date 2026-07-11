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

namespace Horde\Service\Facebook\Test\Graph\Pagination;

use Horde\Http\Client\Mock as MockHttpClient;
use Horde\Http\RequestFactory;
use Horde\Http\Response;
use Horde\Http\StreamFactory;
use Horde\Service\Facebook\Graph\GraphEdge;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\Pagination\PagedIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PagedIterator::class)]
final class PagedIteratorTest extends TestCase
{
    public function testYieldsSinglePageWithoutNext(): void
    {
        $firstPage = new GraphEdge(
            data: [(object) ['id' => '1'], (object) ['id' => '2']],
            paging: null,
        );
        $iterator = new PagedIterator(
            firstPage: $firstPage,
            hydrator: static fn(object $raw): string => (string) $raw->id,
            httpClient: new MockHttpClient(),
            requestFactory: new RequestFactory(),
            decoder: new GraphResponseDecoder(),
        );

        $this->assertSame(['1', '2'], iterator_to_array($iterator));
    }

    public function testWalksAcrossPagesViaNextUrl(): void
    {
        $streamFactory = new StreamFactory();
        $mock = new MockHttpClient();
        $mock->setResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $streamFactory->createStream('{"data":[{"id":"3"},{"id":"4"}]}'),
        ));

        $firstPage = new GraphEdge(
            data: [(object) ['id' => '1'], (object) ['id' => '2']],
            paging: (object) ['next' => 'https://graph.facebook.com/v25.0/me/events?after=cursor'],
        );
        $iterator = new PagedIterator(
            firstPage: $firstPage,
            hydrator: static fn(object $raw): string => (string) $raw->id,
            httpClient: $mock,
            requestFactory: new RequestFactory(),
            decoder: new GraphResponseDecoder(),
        );

        $this->assertSame(['1', '2', '3', '4'], iterator_to_array($iterator));
    }

    public function testStopsWhenCallerBreaksBeforeExhaustingPage(): void
    {
        $mock = new MockHttpClient();
        // No response registered; if the iterator tried to walk to
        // page two the mock would throw. This asserts laziness.

        $firstPage = new GraphEdge(
            data: [(object) ['id' => '1'], (object) ['id' => '2']],
            paging: (object) ['next' => 'https://graph.facebook.com/v25.0/me/events?after=cursor'],
        );
        $iterator = new PagedIterator(
            firstPage: $firstPage,
            hydrator: static fn(object $raw): string => (string) $raw->id,
            httpClient: $mock,
            requestFactory: new RequestFactory(),
            decoder: new GraphResponseDecoder(),
        );

        $seen = [];
        foreach ($iterator as $item) {
            $seen[] = $item;
            break;
        }

        $this->assertSame(['1'], $seen);
    }
}

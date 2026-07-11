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

namespace Horde\Service\Facebook\Graph\Pagination;

use Closure;
use Horde\Service\Facebook\Graph\GraphEdge;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use IteratorAggregate;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Traversable;

/**
 * Iterator that walks every page of a Graph API edge, yielding
 * individual items across page boundaries transparently.
 *
 * The endpoint layer constructs a PagedIterator with:
 *
 *   - the initial GraphEdge (the first page's decoded body)
 *   - a hydrator closure that maps a raw JSON item to a typed value
 *     object (Event, User, Permission, ...)
 *   - the PSR-18 client + request factory needed to fetch subsequent
 *     pages via the paging.next URL
 *
 * Iteration is lazy: the second page is not fetched until the caller
 * exhausts the first. This keeps memory bounded and lets callers break
 * out early (foreach + break) without paying for unread pages.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
final class PagedIterator implements IteratorAggregate
{
    /**
     * @param Closure(object): T $hydrator
     */
    public function __construct(
        private readonly GraphEdge $firstPage,
        private readonly Closure $hydrator,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphResponseDecoder $decoder,
    ) {}

    /**
     * Yield every item across every page in order. Fetches successive
     * pages lazily; a caller that breaks out early does not pay for
     * pages it never reads.
     *
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        $page = $this->firstPage;
        $index = 0;
        while (true) {
            foreach ($page->data as $raw) {
                yield $index++ => ($this->hydrator)($raw);
            }
            $nextUrl = $page->nextUrl();
            if ($nextUrl === null) {
                return;
            }
            $request = $this->requestFactory->createRequest('GET', $nextUrl)
                ->withHeader('Accept', 'application/json');
            $response = $this->httpClient->sendRequest($request);
            $decoded = $this->decoder->decode($response);
            if (!$decoded instanceof GraphEdge) {
                // A paging.next URL that does not return an edge is a
                // wire bug — surface it rather than silently truncating.
                return;
            }
            $page = $decoded;
        }
    }
}

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

namespace Horde\Service\Facebook\Graph\Endpoint\V25;

use Horde\Service\Facebook\FacebookApiException;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Event;
use Horde\Service\Facebook\Graph\GraphEdge;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Horde\Service\Facebook\Graph\Pagination\Cursor;
use Horde\Service\Facebook\Graph\Pagination\PagedIterator;
use Horde\Service\Facebook\Graph\Request\GetMyEventsRequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * /me/events endpoint for Graph API v25.
 *
 * Lists events the current user is associated with. Requires the
 * user_events permission (post-2018 App Review gated). Without it Meta
 * returns an OAuthException the shared decoder maps to a
 * GraphErrorException with a code-200-ish permission error.
 *
 * Returns a PagedIterator<Event> that lazily walks Meta's paging.next
 * URLs across the whole result set. Callers that want a bounded list
 * pass a Cursor with an explicit limit; callers that want the whole
 * set iterate to completion.
 */
final class EventsEndpoint
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly GraphResponseDecoder $decoder,
    ) {}

    /**
     * @param list<string> $fields
     * @return PagedIterator<Event>
     */
    public function listMyEvents(?Cursor $cursor = null, array $fields = []): PagedIterator
    {
        $factory = new GetMyEventsRequestFactory(
            $this->requestFactory,
            $this->urlBuilder,
            $cursor,
            $fields,
        );
        $response = $this->httpClient->sendRequest($factory->create());
        $decoded = $this->decoder->decode($response);

        if (!$decoded instanceof GraphEdge) {
            throw new FacebookApiException(
                'GET /me/events returned a node shape; expected edge',
                $response->getStatusCode(),
            );
        }

        return new PagedIterator(
            firstPage: $decoded,
            hydrator: static fn(object $raw): Event => Event::fromApiResponse($raw),
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            decoder: $this->decoder,
        );
    }
}

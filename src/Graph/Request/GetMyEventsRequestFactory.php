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

namespace Horde\Service\Facebook\Graph\Request;

use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Horde\Service\Facebook\Graph\Pagination\Cursor;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Build a GET /me/events request.
 *
 * Version-neutral. The endpoint layer supplies a version-neutral URL
 * builder pinned to the client's active GraphApiVersion.
 *
 * The caller can constrain the request with a Cursor (before / after /
 * limit) and a list of fields. When both are absent, Meta returns
 * paginated events with its default field set (id, name, start_time,
 * end_time, place, rsvp_status on v25) — good enough for most
 * scheduling views without further tuning.
 */
final class GetMyEventsRequestFactory
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly ?Cursor $cursor = null,
        private readonly array $fields = [],
    ) {}

    public function create(): RequestInterface
    {
        $params = $this->cursor?->toQueryParams() ?? [];
        if ($this->fields !== []) {
            $params['fields'] = implode(',', $this->fields);
        }
        return $this->requestFactory
            ->createRequest('GET', $this->urlBuilder->build('/me/events', $params))
            ->withHeader('Accept', 'application/json');
    }
}

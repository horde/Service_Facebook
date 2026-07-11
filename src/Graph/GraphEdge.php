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

namespace Horde\Service\Facebook\Graph;

/**
 * Typed wrapper over a Graph API "edge" response — a collection at a
 * connection, e.g. GET /me/events or GET /{event-id}/attending.
 *
 * Graph edge responses arrive in the shape:
 *
 *   { "data": [ ...items ], "paging": { "cursors": {...}, "next": "..." } }
 *
 * $data holds the list of decoded objects; the endpoint layer is
 * responsible for mapping each entry to the right per-version value
 * object. $paging is the raw paging block; PagedIterator consumes it
 * to walk subsequent pages via the "next" URL.
 *
 * Both fields are public readonly so hydration and pagination code can
 * read them directly without a method call. Callers who want a typed
 * iterator should reach for PagedIterator rather than iterating $data
 * themselves — PagedIterator handles cross-page walking for them.
 */
final class GraphEdge
{
    /**
     * @param list<object> $data
     */
    public function __construct(
        public readonly array $data,
        public readonly ?object $paging = null,
    ) {}

    /**
     * The absolute URL for the next page of this edge, or null if
     * there are no more pages. Meta emits this as paging.next when
     * more results exist; its absence signals end-of-edge.
     */
    public function nextUrl(): ?string
    {
        if ($this->paging === null) {
            return null;
        }
        if (!isset($this->paging->next) || !is_string($this->paging->next)) {
            return null;
        }
        return $this->paging->next;
    }
}

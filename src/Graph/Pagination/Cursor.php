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

/**
 * Cursor parameters for a Graph API edge request.
 *
 * Graph is cursor-paginated by default, not offset-paginated. Meta
 * returns opaque cursor strings ("before", "after") that the client
 * feeds back in subsequent requests to walk the collection. The
 * "limit" parameter caps the page size; Meta clamps it to its own
 * per-endpoint maximum, which varies.
 *
 * All fields are optional. An empty Cursor sends no pagination
 * parameters and lets Meta pick the default page size. A Cursor with
 * only "after" set fetches the page after the supplied cursor. A
 * Cursor with only "limit" set caps page size at the requested value.
 *
 * PagedIterator constructs these internally when walking to the next
 * page; callers only need to build one directly when they want to
 * start iteration from a specific cursor (e.g. resuming a paginated
 * traversal from a stored position).
 */
final class Cursor
{
    public function __construct(
        public readonly ?string $before = null,
        public readonly ?string $after = null,
        public readonly ?int $limit = null,
    ) {}

    /**
     * Convert this cursor to the query parameters Graph expects.
     * Empty fields are omitted rather than sent as empty strings.
     */
    public function toQueryParams(): array
    {
        $params = [];
        if ($this->before !== null) {
            $params['before'] = $this->before;
        }
        if ($this->after !== null) {
            $params['after'] = $this->after;
        }
        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }
        return $params;
    }
}

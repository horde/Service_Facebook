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
 * Typed wrapper over a Graph API "node" response — a single resource,
 * e.g. GET /me or GET /{event-id}.
 *
 * The Graph API returns node responses as bare JSON objects at the top
 * level (no envelope wrapping, no data key). GraphNode carries the raw
 * decoded object so per-version value objects can hydrate their fields
 * from it via ::fromApiResponse($node->raw), and the endpoint layer can
 * expose typed accessors on top.
 *
 * The wrapper is deliberately thin. It exposes raw as a public
 * readonly property rather than a method, because the primary consumer
 * (value-object hydration) treats it as data, not a resource with
 * semantic accessors of its own. Higher-level shape lives on the
 * value objects themselves.
 */
final class GraphNode
{
    public function __construct(
        public readonly object $raw,
    ) {}
}

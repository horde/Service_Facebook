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

namespace Horde\Service\Facebook\Graph\Value;

use DateTimeImmutable;

/**
 * Shared identity for a Facebook Graph "event" value object across
 * every supported Graph API version.
 *
 * See Horde\Service\Facebook\Graph\Value\User for the general
 * maintenance contract that governs this and every other shared value
 * interface. Summary: this interface promises the least-common-
 * denominator set of fields Meta returns on every supported version's
 * event shape. Version-specific fields (place, RSVP status, end time,
 * owner, attendees, description, etc.) live on the concrete
 * Horde\Service\Facebook\Graph\Endpoint\V{N}\Value\Event class only.
 */
interface Event
{
    /**
     * The Facebook-scoped ID for this event.
     */
    public function id(): string;

    /**
     * The event name/title.
     */
    public function name(): string;

    /**
     * The event's start time. Meta returns ISO 8601 timestamps; the
     * concrete implementation parses to DateTimeImmutable at
     * hydration time so callers never see raw strings.
     */
    public function startTime(): DateTimeImmutable;
}

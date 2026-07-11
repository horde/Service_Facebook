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

/**
 * Shared identity for a permission entry returned by
 * GET /me/permissions across every supported Graph API version.
 *
 * See Horde\Service\Facebook\Graph\Value\User for the maintenance
 * contract. Permission's shape has been stable across every Graph
 * version this library targets — two fields, both always present —
 * so the interface exposes both.
 */
interface Permission
{
    /**
     * The permission name, e.g. "public_profile", "email",
     * "user_events".
     */
    public function name(): string;

    /**
     * The permission status. Meta returns one of "granted" or
     * "declined". Callers should treat unknown values as declined for
     * safety.
     */
    public function status(): string;
}

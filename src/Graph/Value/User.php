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
 * Shared identity for a Facebook Graph "user" value object across
 * every supported Graph API version.
 *
 * Callers who write against this interface get portability across
 * versions but see only the least-common-denominator fields present on
 * every supported version's /me response. Callers who need
 * version-specific fields (e.g. profile picture URL, cover photo,
 * timezone) type-hint the concrete Horde\Service\Facebook\Graph\Endpoint\V{N}\Value\User
 * class directly.
 *
 * Maintenance contract:
 *
 *   1. This interface promises only what Meta returns on every
 *      supported version. If a field is not guaranteed on every
 *      supported version's response, it does not go on the interface.
 *   2. If Meta drops a previously-core field, the interface shrinks
 *      as a semver-major change of this library. We do not fake the
 *      field, we do not return a sentinel from an accessor.
 *   3. The interface does not grow to add version-specific fields. A
 *      field becomes core only once every supported version returns
 *      it.
 *
 * Interface accessors are methods rather than public readonly
 * properties because PHP interfaces cannot declare public properties;
 * concrete per-version classes still expose their fields as public
 * readonly properties for direct access. This interface is the
 * cross-version portable surface; the concrete classes are where the
 * full shape lives.
 */
interface User
{
    /**
     * The Facebook-scoped ID for this user. Present on every Graph API
     * version and every response shape that returns a user object.
     */
    public function id(): string;

    /**
     * The user's display name. Present on every Graph API version;
     * subject to Meta's per-scope visibility rules (may return empty
     * for tokens that lack public_profile).
     */
    public function name(): string;
}

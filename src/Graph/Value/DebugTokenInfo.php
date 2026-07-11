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
 * Shared identity for the response of GET /debug_token across every
 * supported Graph API version.
 *
 * The debug_token endpoint returns metadata about an access token —
 * whether it is valid, which app issued it, which user (if any) it is
 * bound to, when it expires, and which scopes it carries. Callers use
 * it before making privileged calls to verify a token's fitness, or
 * during token introspection flows where a server wants to know what
 * a client-supplied token grants.
 *
 * See Horde\Service\Facebook\Graph\Value\User for the interface
 * maintenance contract.
 */
interface DebugTokenInfo
{
    /**
     * The application ID the token was issued for.
     */
    public function appId(): string;

    /**
     * The user ID the token is bound to, or null for app tokens that
     * are not user-bound.
     */
    public function userId(): ?string;

    /**
     * Whether Meta considers this token currently valid.
     */
    public function isValid(): bool;

    /**
     * When the token expires. May be null for tokens Meta reports as
     * non-expiring (rare in modern flows).
     */
    public function expiresAt(): ?DateTimeImmutable;
}

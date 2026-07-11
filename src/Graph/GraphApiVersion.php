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
 * Closed enumeration of Facebook Graph API versions this library will
 * send requests on.
 *
 * The enum is deliberately minimal. It carries no release dates, no
 * deprecation predicates, no wire-retirement metadata. Whether Meta
 * still accepts a given version on the wire is Meta's business — if
 * they reject a request, callers get a GraphErrorException like any
 * other server error. The library's only claim is: these are the
 * versions our code can construct and dispatch against.
 *
 * Adding a new version (say v26.0) when Meta ships one is a one-line
 * change: add case V26_0 = 'v26.0'; optionally add a
 * Graph/Endpoint/V26/ subtree if any endpoint shape drifted; optionally
 * flip default() to return the new case.
 *
 * Removing a case is a semver-major change on the library's own
 * schedule — see the retirement policy documented in the port plan.
 * Value-object subtrees for a removed enum case may remain in the
 * source tree for hydration and replay use cases.
 */
enum GraphApiVersion: string
{
    case V25_0 = 'v25.0';
    case V24_0 = 'v24.0';
    case V23_0 = 'v23.0';
    case V22_0 = 'v22.0';

    /**
     * The version segment as it appears in a Graph URL (e.g. "v25.0").
     * The URL builder prepends this with a leading slash when
     * assembling requests.
     */
    public function urlSegment(): string
    {
        return $this->value;
    }

    /**
     * The default version new FacebookApiConfig instances target when
     * the caller does not specify one. Bumped only when a newer
     * supported version has proven itself stable enough to be the
     * out-of-the-box choice.
     */
    public static function default(): self
    {
        return self::V25_0;
    }
}

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

namespace Horde\Service\Facebook;

use Horde\Service\Facebook\Graph\GraphApiVersion;

/**
 * Immutable configuration for a FacebookApiClient instance.
 *
 * Carries the Graph API base URL and the default GraphApiVersion the
 * client will target when a per-request override is not supplied. The
 * config itself is a plain data holder — no side effects, no lazy
 * initialization, no state. FacebookApiClient::withVersion() constructs
 * a new FacebookApiConfig plus a new FacebookApiClient rather than
 * mutating an existing config.
 *
 * The default base URL points at the public Facebook Graph endpoint.
 * Override it only for local proxying, Meta's sandbox environments, or
 * an on-premises equivalent. The version segment (e.g. /v25.0/) is
 * prepended by the URL builder at request time and is NOT part of
 * baseUrl.
 */
final class FacebookApiConfig
{
    public function __construct(
        public readonly string $baseUrl = 'https://graph.facebook.com',
        public readonly GraphApiVersion $version = GraphApiVersion::V25_0,
    ) {}
}

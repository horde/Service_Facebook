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
 * Builds Graph API URLs from a version-neutral endpoint path.
 *
 * The design contract is documented in the port plan under R4: no
 * endpoint module and no request factory writes a version segment into
 * a URL. They all pass version-less paths (/me, /me/events,
 * /{event-id}) and this class prepends the /v{N}.{n}/ segment when the
 * request is built. That keeps the endpoint layer version-neutral by
 * construction and makes adding a new supported version a matter of
 * (a) an enum case and (b) at most a per-version subdirectory of
 * shape-drift overrides.
 *
 * URL assembly is deliberately simple. The base URL is expected to
 * already include the scheme, host, and no path segment. The version
 * segment is joined with a single leading slash. The endpoint path is
 * appended verbatim if it already begins with a slash, otherwise a
 * slash is inserted. Query parameters are appended via http_build_query
 * with PHP_QUERY_RFC3986 to keep spaces encoded as %20 rather than +,
 * which matches Meta's own encoding and avoids interop surprises with
 * clients that decode both differently.
 */
final class GraphUrlBuilder
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly GraphApiVersion $version,
    ) {}

    /**
     * Assemble a full Graph API URL for the supplied version-neutral
     * endpoint path and optional query parameters.
     *
     * $endpointPath is version-less. Pass "/me" or "me" — either works.
     * Do NOT pass "/v25.0/me"; the version segment is prepended here.
     *
     * $queryParams may contain scalars, nested arrays (encoded per
     * http_build_query rules), or Meta's field-list convention
     * (comma-joined string values for the "fields" parameter).
     */
    public function build(string $endpointPath, array $queryParams = []): string
    {
        $path = $endpointPath === '' ? '' : ($endpointPath[0] === '/' ? $endpointPath : '/' . $endpointPath);
        $url = rtrim($this->baseUrl, '/') . '/' . $this->version->urlSegment() . $path;

        if ($queryParams !== []) {
            $url .= '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /**
     * The active Graph API version. Exposed for diagnostics and for
     * request factories that want to record the version they built
     * against without holding a separate reference.
     */
    public function version(): GraphApiVersion
    {
        return $this->version;
    }
}

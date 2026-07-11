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
 * Static helpers for building Facebook-hosted profile URLs.
 *
 * These are pure URL builders. They make no HTTP calls, require no
 * access token, and hold no state. Callers use them to render a link
 * to a user's Facebook profile page or their profile picture — the
 * two operations that (a) do not require any Graph API interaction
 * at all and (b) callers keep needing in signature blocks, audit
 * logs, and directory listings.
 *
 * Corresponds to the legacy Horde_Service_Facebook_Users::getProfileLink()
 * and ::getThumbnail() methods, which are the only two members of the
 * legacy Users module that ported cleanly (everything else on that
 * module targeted user-lookup endpoints Meta has since gutted for
 * third parties).
 *
 * profileLink() returns https://www.facebook.com/{uid}. Meta has
 * honoured this URL shape for over a decade and continues to; the
 * URL redirects to the user's profile if the caller has permission
 * to see it or to Meta's login/error page otherwise. It does not
 * leak any information about the user beyond "does an account with
 * this id exist" — which is not a secret.
 *
 * thumbnailUrl() returns https://graph.facebook.com/{version}/{uid}/picture
 * with the URL-builder-supplied version segment (defaulting to
 * GraphApiVersion::default()). The URL dereferences at request time
 * to Meta's currently-served profile picture; Meta returns a 302 to
 * the actual image. Query parameters can control the picture size
 * (type=small, type=large, type=square, or explicit width/height);
 * this helper accepts them as an optional array.
 */
final class ProfileUrls
{
    private const FACEBOOK_HOST = 'https://www.facebook.com';
    private const GRAPH_HOST = 'https://graph.facebook.com';

    /**
     * Build the URL to a user's public Facebook profile page.
     *
     * The returned URL is https://www.facebook.com/{uid}. Callers
     * render it in link elements, email signatures, audit views —
     * anywhere a Facebook profile pointer belongs.
     *
     * $uid is passed through rawurlencode so exotic values (numeric
     * uids, vanity names like "zuck") both produce a valid URL.
     * Meta accepts either form; the URL builder does not distinguish.
     */
    public static function profileLink(string $uid): string
    {
        return self::FACEBOOK_HOST . '/' . rawurlencode($uid);
    }

    /**
     * Build the URL to a user's profile picture.
     *
     * The returned URL is /{version}/{uid}/picture on the Graph host.
     * Meta serves a 302 redirect to the actual image binary; callers
     * either use the URL as an <img src="..."> attribute (browsers
     * follow the redirect transparently) or fetch it themselves and
     * cache the resolved image.
     *
     * $version defaults to GraphApiVersion::default(). Pinning here
     * is only meaningful if Meta ever ships a version-specific
     * change to /picture semantics (they have not in over a decade,
     * and are not expected to).
     *
     * $options is an optional map of query parameters Meta accepts:
     *   - 'type' => 'small' | 'normal' | 'large' | 'square'
     *   - 'width' => int (pixels)
     *   - 'height' => int (pixels)
     *   - 'redirect' => 'false' to get a JSON envelope with the
     *                   picture URL instead of a redirect (useful
     *                   for server-side rendering to avoid the
     *                   extra round trip)
     *
     * @param array<string, string|int> $options
     */
    public static function thumbnailUrl(
        string $uid,
        ?GraphApiVersion $version = null,
        array $options = [],
    ): string {
        $segment = ($version ?? GraphApiVersion::default())->urlSegment();
        $url = self::GRAPH_HOST . '/' . $segment . '/' . rawurlencode($uid) . '/picture';
        if ($options !== []) {
            $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
        }
        return $url;
    }
}

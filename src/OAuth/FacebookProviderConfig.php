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

namespace Horde\Service\Facebook\OAuth;

use Horde\OAuth\Client\ProviderConfig;
use Horde\Service\Facebook\Graph\GraphApiVersion;

/**
 * Factory producing a Horde\OAuth\Client\ProviderConfig with Meta's
 * OAuth2 / OIDC endpoint URLs for a given Graph API version.
 *
 * The Facebook package does not re-implement OAuth2. Callers wire the
 * flow with Horde\OAuth\Client\OAuth2Client and pass the provider
 * config returned here. Every URL is version-pinned so the OAuth2
 * exchange rides the same version as the Graph API calls that follow.
 *
 * Endpoints (Meta's documented URL shape):
 *
 *   Authorization:    https://www.facebook.com/v{N}.{n}/dialog/oauth
 *   Token:            https://graph.facebook.com/v{N}.{n}/oauth/access_token
 *   Revocation:       https://graph.facebook.com/v{N}.{n}/me/permissions
 *                     (Meta uses DELETE-on-permissions as revocation)
 *   JWKS (OIDC):      https://www.facebook.com/.well-known/oauth/openid/jwks/
 *                     (version-neutral; not versioned in Meta's URL scheme)
 *
 * The issuer string in the returned ProviderConfig is
 * https://www.facebook.com — Meta's documented OIDC issuer. Callers
 * verifying id_tokens should pass this as the expected issuer to
 * FacebookOidcSupport.
 *
 * Meta does not publish an OIDC discovery document at
 * .well-known/openid-configuration, so this factory hardcodes the
 * endpoints from Meta's Login-for-the-Web documentation. If Meta
 * changes the URL shape (they historically have not for these
 * endpoints), this file is the one place to update.
 */
final class FacebookProviderConfig
{
    public const AUTHORIZATION_HOST = 'https://www.facebook.com';
    public const GRAPH_HOST = 'https://graph.facebook.com';
    public const ISSUER = 'https://www.facebook.com';
    public const JWKS_URI = 'https://www.facebook.com/.well-known/oauth/openid/jwks/';

    /**
     * The scopes this factory documents as commonly-requested. This
     * is not an exhaustive list — Meta ships dozens of scopes and the
     * useful set depends heavily on the app. Callers may pass any
     * scope Meta accepts; this list is for consumers who want to
     * enumerate what a fresh Facebook Login flow might reasonably
     * request without reading Meta's docs first.
     *
     * @var list<string>
     */
    public const COMMON_SCOPES = [
        'public_profile',
        'email',
        'openid',
        'user_events',
        'user_photos',
        'user_link',
        'user_birthday',
    ];

    public static function forVersion(GraphApiVersion $version): ProviderConfig
    {
        $segment = $version->urlSegment();

        return new ProviderConfig(
            issuer: self::ISSUER,
            authorizationEndpoint: self::AUTHORIZATION_HOST . '/' . $segment . '/dialog/oauth',
            tokenEndpoint: self::GRAPH_HOST . '/' . $segment . '/oauth/access_token',
            userinfoEndpoint: null,
            jwksUri: self::JWKS_URI,
            revocationEndpoint: self::GRAPH_HOST . '/' . $segment . '/me/permissions',
            introspectionEndpoint: self::GRAPH_HOST . '/' . $segment . '/debug_token',
            scopesSupported: self::COMMON_SCOPES,
            responseTypesSupported: ['code', 'token', 'code token'],
            grantTypesSupported: ['authorization_code', 'refresh_token'],
            tokenEndpointAuthMethodsSupported: ['client_secret_post'],
            idTokenSigningAlgValuesSupported: ['RS256'],
        );
    }
}

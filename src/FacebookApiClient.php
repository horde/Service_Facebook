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

use Horde\Service\Facebook\Graph\Endpoint\EndpointResolver;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\DebugTokenInfo;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Event;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Permission;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\User;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\Pagination\Cursor;
use Horde\Service\Facebook\Graph\Pagination\PagedIterator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Facade for the Facebook Graph API client library.
 *
 * A single entry point that dispatches to per-version endpoint modules
 * beneath the Graph\Endpoint\V{N}\ namespace, so callers write
 * version-neutral code by default and pin a version via withVersion()
 * only when they need to. The default version comes from
 * GraphApiVersion::default() and is bumped only when a newer supported
 * version has proven stable enough to be the out-of-the-box choice.
 *
 * Construction. The primary constructor takes PSR-18 / PSR-17
 * interfaces so callers can wire any conforming HTTP client (horde/http
 * with its bundled mocks in tests, Guzzle, Symfony HttpClient, etc.).
 * The static create() factory is a convenience for the common case; it
 * constructs a FacebookApiConfig with sensible defaults. Consumers who
 * want batteries-included Horde HTTP wiring can construct a
 * Horde\Http\Client themselves and hand it to create().
 *
 * Immutability. withVersion() returns a NEW FacebookApiClient bound to
 * the requested version rather than mutating this instance. Mirrors
 * the Horde\Http\Uri with*() idiom the framework enforces for immutable
 * value-carrying types. Every public method below reads the version
 * from $this->config, so a caller who holds two client instances
 * pinned to different versions gets clean concurrent behaviour.
 *
 * OAuth2 and OIDC. The client accepts a plain ClientInterface. Callers
 * that need Bearer token authentication wrap their PSR-18 client with
 * Horde\OAuth\Client\AuthenticatedHttpClient and hand the wrapper in.
 * The Facebook package owns only Facebook-specific concerns (provider
 * config, token debug, error mapping); the OAuth2 dance itself is
 * horde/oauth's responsibility.
 *
 * Return types are the concrete V25 value objects. Callers who want
 * portable version-neutral signatures should type-hint the shared
 * Horde\Service\Facebook\Graph\Value\* interfaces those classes
 * implement; the concrete return type here documents which fields are
 * actually available on the default version. When the client is
 * pinned to an older version, the returned object is still the V25
 * concrete class in the current MVP — v24/v23/v22 fall through to
 * V25's hydrator per the EndpointResolver match arms. When a genuine
 * shape drift lands in a future version, both the resolver and the
 * facade's declared return type update in the same PR.
 */
final class FacebookApiClient
{
    private readonly EndpointResolver $resolver;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly FacebookApiConfig $config,
        private readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->resolver = new EndpointResolver($httpClient, $requestFactory, $config);
    }

    /**
     * Convenience factory that constructs a FacebookApiConfig from
     * common parameters. Prefer this over calling the constructor
     * directly when defaults are acceptable; use the constructor when
     * a caller has a fully-formed FacebookApiConfig to hand in.
     *
     * When $version is null, GraphApiVersion::default() decides.
     */
    public static function create(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        ?StreamFactoryInterface $streamFactory = null,
        ?GraphApiVersion $version = null,
        string $baseUrl = 'https://graph.facebook.com',
    ): self {
        return new self(
            $httpClient,
            $requestFactory,
            new FacebookApiConfig(
                baseUrl: $baseUrl,
                version: $version ?? GraphApiVersion::default(),
            ),
            $streamFactory,
        );
    }

    /**
     * Return a new FacebookApiClient bound to the requested Graph API
     * version. Does not mutate this instance. The returned client
     * shares the same HTTP client, request factory, and stream factory
     * as this one, so wrapping and mocking are preserved across the
     * version switch.
     */
    public function withVersion(GraphApiVersion $version): self
    {
        return new self(
            $this->httpClient,
            $this->requestFactory,
            new FacebookApiConfig(
                baseUrl: $this->config->baseUrl,
                version: $version,
            ),
            $this->streamFactory,
        );
    }

    /**
     * The Graph API version this client is currently pinned to.
     * Useful for logging, diagnostics, and version-aware caller code
     * that needs to record which shape a response was captured under.
     */
    public function version(): GraphApiVersion
    {
        return $this->config->version;
    }

    /**
     * Fetch metadata about the user represented by the current access
     * token via GET /me. The fields list is optional; an empty list
     * defers to Meta's default projection (id and name only). Callers
     * requesting email, profile link, or name breakdown must both
     * include the field name here AND hold the matching scope on their
     * token — Meta silently omits fields the token cannot see.
     *
     * @param list<string> $fields
     */
    public function getMe(array $fields = []): User
    {
        return $this->resolver->me($this->config->version)->get($fields);
    }

    /**
     * List events the current user is associated with via
     * GET /me/events. Requires the user_events permission on the
     * access token (post-2018 App Review gated). Returns a lazy
     * PagedIterator that walks Meta's paging.next URLs across every
     * page; callers may break out early with foreach + break to bound
     * cost.
     *
     * The method is named listMyUpcomingEvents() to match the port
     * plan's public surface. In practice /me/events returns both
     * upcoming and recently-past events; callers who need a strict
     * upcoming-only filter must post-filter by startTime themselves,
     * or supply a Cursor + fields projection that Meta interprets
     * accordingly.
     *
     * @param list<string> $fields
     * @return PagedIterator<Event>
     */
    public function listMyUpcomingEvents(?Cursor $cursor = null, array $fields = []): PagedIterator
    {
        return $this->resolver->events($this->config->version)->listMyEvents($cursor, $fields);
    }

    /**
     * List permissions currently granted (and declined) on the access
     * token via GET /me/permissions. Meta returns the full permission
     * list on a single page; this method eagerly materialises it into
     * a plain array rather than a PagedIterator.
     *
     * @return list<Permission>
     */
    public function listMyPermissions(): array
    {
        return $this->resolver->permissions($this->config->version)->listMyPermissions();
    }

    /**
     * Revoke a previously-granted permission from the current access
     * token via DELETE /me/permissions/{permission}. The token itself
     * remains valid but loses the ability to access the revoked
     * scope. Throws GraphErrorException if Meta rejects the call.
     */
    public function revokePermission(string $permission): void
    {
        $this->resolver->permissions($this->config->version)->revoke($permission);
    }

    /**
     * Introspect a token via GET /debug_token. $inputToken is the
     * token being verified; $appAccessToken is the caller's own
     * app-level token authorising the introspection call. Returns
     * metadata (validity, app_id, user_id, expiry, scopes) as a
     * typed value object.
     *
     * Useful for token-fitness verification before making privileged
     * calls, and for server-side introspection flows where a
     * client-supplied token needs to be checked before use.
     */
    public function debugToken(string $inputToken, string $appAccessToken): DebugTokenInfo
    {
        return $this->resolver->debugToken($this->config->version)->get($inputToken, $appAccessToken);
    }
}

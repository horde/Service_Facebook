# horde/service_facebook

A modern PSR-4 client for Facebook's Graph API. Targets Graph v25 for now but is ready to actively support for a rolling window of prior versions once graph v26 is out.
Integrates with `horde/oauth` for OAuth2/OIDC flows and `horde/jwt` for
`id_token` verification.

**Status:** Partial

 The legacy PSR-0 tree under
`lib/Horde/Service/Facebook/` targets Facebook's REST API (retired
2020) and FQL (retired 2016) and does not work against modern Facebook.

The new PSR-4 tree under `src/` is the supported surface. New and updated code
should use `Horde\Service\Facebook\FacebookApiClient`
The legacy `Horde_Service_Facebook` classes are kept around to prevent code breaking on "unknown class", i.e. when it shipped the old client as a side concern and didn't really exercise facebook REST API.

## Installation

```bash
composer require horde/service_facebook
```

Requires PHP 8.1+. The library is strict-PSR at runtime. Bring your own
PSR-18 client (any conforming implementation. `horde/http`, Guzzle,
Symfony HttpClient). Bring your own PSR-17 request and stream
factories. The Facebook client wires everything through them.

Batteries-included wiring with `horde/http`:

```bash
composer require horde/service_facebook horde/http
```

## Quick Start

Fetch the current user's profile:

```php
use Horde\Http\Client;
use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\OAuth\Client\AuthenticatedHttpClient;
use Horde\OAuth\Client\TokenSet;
use Horde\Service\Facebook\FacebookApiClient;

// Bearer-authenticated PSR-18 client.
$tokenSet = new TokenSet(
    accessToken: $userAccessToken,
    tokenType: 'Bearer',
    expiresAt: null,
    refreshToken: null,
    scope: null,
);
$httpClient = new AuthenticatedHttpClient(new Client(), $tokenSet);

$fb = FacebookApiClient::create(
    httpClient: $httpClient,
    requestFactory: new RequestFactory(),
    streamFactory: new StreamFactory(),
);

$me = $fb->getMe(['id', 'name', 'email']);
echo $me->name;   // "Ada Lovelace"
echo $me->email;  // "ada@example.org" (if the token holds the email scope)
```

## Version Pinning

The default version comes from `GraphApiVersion::default()` (currently
`v25.0`). To pin to an older supported version:

```php
use Horde\Service\Facebook\Graph\GraphApiVersion;

$fbV24 = $fb->withVersion(GraphApiVersion::V24_0);
$events = iterator_to_array($fbV24->listMyUpcomingEvents());
```

`withVersion()` returns a **new immutable client**. The original client
remains pinned to its previous version. You can hold two client
instances side by side without interference.

Supported versions are exposed as enum cases:

- `GraphApiVersion::V25_0` (current default)
- `GraphApiVersion::V24_0`
- `GraphApiVersion::V23_0`
- `GraphApiVersion::V22_0`

Note: The older APIs aren't really supported - This feature is designing against a foreaseeable shift on facebook side to V26 or V27 while still supporting V25 for a reasonable time.

Attempting to pass a version the library doesn't ship is a
**compile-time error**. You cannot construct an unknown enum case.
Whether Meta still accepts your pinned version on the wire is Meta's
business. If they reject the request you get a `GraphErrorException`
like any other server error.

See [doc/VERSIONING.md](doc/VERSIONING.md) for how new Facebook versions
land in the library and when old ones eventually leave.

## API Methods

The MVP surface is deliberately narrow. The endpoints Meta still
supports for third-party apps on modern Graph API, plus the
token-management primitives.

### `getMe(array $fields = []): User`

`GET /me`. Empty `$fields` defers to Meta's default projection. Include
fields you want back. `email` requires the `email` scope. Name
breakdown requires `public_profile`. And so on. Meta silently omits
fields the token cannot see rather than raising an error, so a
stripped-down response usually means a stripped-down scope grant.

### `listMyUpcomingEvents(?Cursor $cursor = null, array $fields = []): PagedIterator`

`GET /me/events`. Requires the `user_events` permission on the access
token (App Review gated by Meta post-2018). Returns a lazy iterator
over `Event` value objects. Paging is handled transparently. The
iterator walks Meta's `paging.next` URLs so callers get every page or
`break` out early to bound cost.

```php
use Horde\Service\Facebook\Graph\Pagination\Cursor;

foreach ($fb->listMyUpcomingEvents(new Cursor(limit: 25)) as $event) {
    echo $event->name, ' @ ', $event->startTime->format('Y-m-d H:i'), "\n";
}
```

### `listMyPermissions(): list<Permission>`

`GET /me/permissions`. Meta returns a small, unpaginated list. This
method materialises the full array of `Permission` value objects. Each
has `name()`, `status()`, and an `isGranted()` convenience.

```php
foreach ($fb->listMyPermissions() as $perm) {
    if (!$perm->isGranted()) {
        echo $perm->name(), ' status: ', $perm->status(), "\n";
    }
}
```

### `revokePermission(string $permission): void`

`DELETE /me/permissions/{permission}`. Revokes a previously-granted
scope from the current token. The token itself remains valid but loses
access to the revoked scope. Meta returns `{"success":true}` on
success. Anything else raises `GraphErrorException`.

### `debugToken(string $inputToken, string $appAccessToken): DebugTokenInfo`

`GET /debug_token`. Introspects a token. The one being examined
(`$inputToken`) is usually a user or client token whose fitness you
want to verify. The second token (`$appAccessToken`) is your app-level
token authorising the introspection call (typically
`"{$appId}|{$appSecret}"`).

```php
$info = $fb->debugToken($someUserToken, $appId . '|' . $appSecret);
if (!$info->isValid()) {
    // Reject the request. The caller's token is no good.
}
if ($info->expiresAt !== null && $info->expiresAt <= new DateTimeImmutable('+5 minutes')) {
    // Nearly expired. Trigger a refresh.
}
```

## OAuth2 and OIDC

The Facebook package does not re-implement OAuth2. It provides a
`FacebookProviderConfig` factory that returns a
`Horde\OAuth\Client\ProviderConfig` pre-populated with Meta's versioned
endpoint URLs. Callers wire the actual authorization flow with
`Horde\OAuth\Client\OAuth2Client`.

### Authorization flow

```php
use Horde\OAuth\Client\OAuth2Client;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\OAuth\FacebookProviderConfig;

$providerConfig = FacebookProviderConfig::forVersion(GraphApiVersion::V25_0);
$oauth = new OAuth2Client(
    $httpClient,
    $requestFactory,
    $streamFactory,
    $providerConfig,
    clientId: getenv('FB_APP_ID'),
    clientSecret: getenv('FB_APP_SECRET'),
);

// 1. Redirect the user to Facebook.
$state = bin2hex(random_bytes(16));
$authUrl = $oauth->getAuthorizationUrl(
    redirectUri: 'https://example.org/callback',
    scopes: ['public_profile', 'email', 'user_events'],
    state: $state,
);
header('Location: ' . $authUrl);

// 2. In the callback handler, exchange the code for a TokenSet.
$tokenSet = $oauth->exchangeCode($_GET['code']);

// 3. Store $tokenSet->accessToken (and $tokenSet->refreshToken if present).
// 4. Wrap your PSR-18 client and hand it to FacebookApiClient::create().
```

### id_token verification (Facebook Login with `openid` scope)

When you request the `openid` scope, Meta returns an `id_token`
alongside the access token. The `id_token` is a signed JWT carrying
user identity claims (`sub`, `aud`, `iss`, `exp`, `iat`, plus any
nonce you passed).

Verification via `FacebookOidcSupport`:

```php
use Horde\Service\Facebook\OAuth\FacebookOidcSupport;
use Horde\Service\Facebook\OAuth\FacebookProviderConfig;

$oidc = new FacebookOidcSupport($httpClient, $requestFactory);

$verified = $oidc->verifyIdToken($idToken, [
    'verify_iss' => FacebookProviderConfig::ISSUER,
    'verify_aud' => getenv('FB_APP_ID'),
]);

$userId = $verified->getSubject();
$claims = $verified->getClaims();
```

The helper fetches Meta's JWKS document, selects the key matching the
token's `kid` header, and delegates to `Horde\Jwt\TokenDecoder` for
signature and claims verification. Nonce verification is left to the
caller because it involves state you stored between the authorize
redirect and the callback.

## Error Handling

Every wire error carrying a Meta error envelope becomes a
`GraphErrorException`:

```php
use Horde\Service\Facebook\Graph\GraphErrorException;

try {
    $events = iterator_to_array($fb->listMyUpcomingEvents());
} catch (GraphErrorException $e) {
    if ($e->error()->isTokenExpired()) {
        // Refresh the token, then retry.
    }
    if ($e->error()->isRateLimit()) {
        // Back off. Check e->error()->isAppRateLimit() vs isUserRateLimit()
        // for the specific limit.
    }
    if ($e->error()->isPermissionMissing()) {
        // Ask the user to grant the missing scope.
    }
    throw $e;
}
```

Predicates on `GraphError`:

- `isAuth()`. `code === 190` (any OAuthException-family).
- `isTokenExpired()`. `code === 190` with subcode 463 or 467.
- `isRateLimit()`. `code` in `{4, 17, 32, 613}`.
- `isAppRateLimit()`. `code === 4`.
- `isUserRateLimit()`. `code === 17`.
- `isPermissionMissing()`. `code === 200`.
- `isTransient()`. `code === 1` or `code === 2`.

**Do not string-match `$e->getMessage()`.** Meta rewords error messages
without notice. Use the predicates.

Malformed error responses (HTTP 4xx/5xx with no parseable Meta
envelope) throw the plain `FacebookApiException` base class instead.
Catch that if you want to handle every failure mode uniformly.

## Value Objects

Return types are per-version concrete classes under
`Horde\Service\Facebook\Graph\Endpoint\V{N}\Value\`. For the current
default that means `V25\Value\User`, `V25\Value\Event`,
`V25\Value\Permission`, and `V25\Value\DebugTokenInfo`. Each carries
version-specific fields as public readonly properties.

For version-portable signatures, type-hint the **shared interfaces** in
`Horde\Service\Facebook\Graph\Value\`. `User`, `Event`, `Permission`,
`DebugTokenInfo`. These expose only fields present on every supported
version's response, via method accessors:

```php
use Horde\Service\Facebook\Graph\Value\Event;

// Version-portable. Accepts any V{N}\Value\Event.
function renderEventSummary(Event $event): string
{
    return $event->name() . ' @ ' . $event->startTime()->format('Y-m-d H:i');
}
```

When you need v25-specific fields (`rsvpStatus`, `placeName`,
`coverPhotoUrl`), type-hint the concrete class directly and read the
public properties OR reflect on capability interfaces

Value objects are pure data holders. They construct from decoded JSON
via `::fromApiResponse(object $data): self` and have no dependency on
the HTTP client, config, or version enum. That makes them independently
useful for hydrating stored payloads (database rows, cache entries,
replay fixtures), not only live API responses.

## Profile URL Helpers

Two pure URL builders. No API call, no token required. For rendering
Facebook profile links and profile picture URLs. Corresponds to the
legacy client's `getProfileLink()` and `getThumbnail()` methods, the
only members of the legacy `Users` module that ported cleanly.

```php
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\ProfileUrls;

// Link to a user's public profile page.
$link = ProfileUrls::profileLink('12345');
// => https://www.facebook.com/12345

$link = ProfileUrls::profileLink('zuck');
// => https://www.facebook.com/zuck

// Profile picture URL. Meta serves a 302 to the actual image.
$thumb = ProfileUrls::thumbnailUrl('12345');
// => https://graph.facebook.com/v25.0/12345/picture

// With size/type options.
$thumb = ProfileUrls::thumbnailUrl('12345', GraphApiVersion::V25_0, [
    'type' => 'large',
]);
$thumb = ProfileUrls::thumbnailUrl('12345', null, [
    'width' => 200,
    'height' => 200,
]);
```

## What This Library Does Not Do

Explicitly out of scope for the MVP:

- Photo/video multipart upload.
- Batch requests (`/?batch=[...]`).
- The `pages_*` capability tree (page-content management is a separate
  product surface and will be scoped separately when a caller needs
  it).
- Webhooks / real-time updates.

The legacy `lib/Horde/Service/Facebook/` tree that targets these
features is retained for one release cycle for backward autoload
compatibility. **Do not use it in new code.** It calls endpoints
Facebook no longer serves.

For the full catalog of features the library does not currently ship,
including known deferrals (long-lived token exchange, batch requests)
and larger capability areas (Login-with-Facebook identity mapping,
Pages, Meta Business Manager), see
[doc/MISSING_FEATURES.md](doc/MISSING_FEATURES.md).

## Legacy Coexistence

The `lib/` PSR-0 tree stays in place though largely defunct. The `src/` PSR-4 tree is the
supported surface. There is no forwarding between them. New callers
use the PSR-4 namespace. Old callers keep syntactically correct (against dead
endpoints) so no code breaks by merely depending on the classes. Still migration is due.

Migrating existing callers off `lib/`? Start with
[doc/UPGRADING.md](doc/UPGRADING.md) for the class-to-class map and
concrete migration examples.

## Testing

```bash
composer install
phpunit
```

Uses PHPUnit 11+. Tests run against `test/unit`. Integration tests
under `test/integration` require live Facebook credentials via
environment variables and are excluded from the default suite.

## License

BSD-2-Clause. See `LICENSE`.

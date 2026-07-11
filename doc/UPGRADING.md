# Upgrading Service_Facebook

This guide covers the migration from the legacy
`lib/Horde/Service/Facebook/` PSR-0 client (`2.x`) to the modern PSR-4
`Horde\Service\Facebook\` client (`3.x`). The two trees coexist in one
release cycle for backward autoload compatibility. New code should target
the PSR-4 tree exclusively.

For the release-time and API-version lifecycle policy (how we handle
Facebook shipping v26 and retiring v22), see [VERSIONING.md](VERSIONING.md).

## TL;DR

- Legacy `Horde_Service_Facebook*` classes still autoload at `3.x` but
  they target endpoints Facebook retired years ago. They do not work
  against the live Graph API and are not fixable. Migrate off them.
- New code targets `Horde\Service\Facebook\FacebookApiClient` and the
  value-object trees under `Horde\Service\Facebook\Graph\`.
- The MVP surface is narrow by design: `/me`, `/me/events`,
  `/me/permissions`, `/debug_token`, plus OAuth2/OIDC provider-config.
  Most of the legacy surface (REST API, FQL, News Feed, user-timeline
  writes, Notes, Notifications) has no modern replacement because
  Facebook removed the endpoints, not because we didn't port them.
- OAuth2 is delegated to `horde/oauth`. `id_token` verification is
  delegated to `horde/jwt`. The Facebook package owns only
  Facebook-specific concerns.
- HTTP is strict-PSR. Bring your own PSR-18 client. `horde/http` works
  out-of-the-box. Guzzle, Symfony HttpClient or any conforming
  implementation works equally well.

## What died with Facebook (not with us)

Before showing per-method migrations, an honest inventory of what has
no modern equivalent. This is not "we didn't port it". It is "Facebook
removed the endpoint and no v25 replacement exists":

- **REST API.** `api.facebook.com/method/*` was retired in **August
  2020**. Every `callMethod('facebook.foo.bar', ...)` call in legacy
  code is dead.
- **FQL.** Retired in **August 2016**. The whole legacy `Fql` module
  and the FQL-based implementation of `Events::get()` are
  unrecoverable in their original shape.
- **News Feed reading.** `me/home` and `me/feed` for user timelines
  were removed in 2018. Never coming back for third parties.
- **User-timeline writes.** Posting, commenting, liking on user
  timelines is removed. Pages have their own separate API surface
  (`pages_*` scopes, App Review gated).
- **User event creation and RSVP.** `POST {uid}/events` and RSVP
  endpoints are gone for user events. Pages can still create page
  events via a separate scope family.
- **Friend lists, "all my friends".** Post-2014, the friends edge
  returns only friends who also use the same app. Legacy code
  assuming a full friend graph has never worked in modern Graph.
- **Photo tagging on user photos, user album creation.** Removed.
- **Notes.** The whole product was retired by Facebook in 2020.
- **Notifications** (`facebook.notifications.get`). Removed with the
  REST API.

If your legacy caller depended on any of the above, the modern client
has no replacement. Options are: delete the caller, rework the feature
to use what Facebook still exposes (pages APIs, page content or
nothing) or find a different data source entirely.

## Class-to-class map

The legacy tree exposed a root `Horde_Service_Facebook` with lazy
sub-object accessors (`->events`, `->auth`, `->photos` and so on). The
modern tree exposes a single facade `FacebookApiClient` with named
methods:

| Legacy class / method | Modern replacement | Notes |
|---|---|---|
| `new Horde_Service_Facebook($appId, $secret, $ctx)` | `FacebookApiClient::create($psr18, $psr17)` | App credentials go through `horde/oauth`, not into the client constructor. |
| `Horde_Service_Facebook_Auth::getOAuthUrl(...)` | `Horde\OAuth\Client\OAuth2Client::getAuthorizationUrl(...)` | Provider config via `FacebookProviderConfig::forVersion($v)`. |
| `Horde_Service_Facebook_Auth::getSessionKey($code, $callback)` | `Horde\OAuth\Client\OAuth2Client::exchangeCode($code)` | Returns a `TokenSet`, not a bare string. |
| `Horde_Service_Facebook_Auth::setSession($accessToken)` | wrap PSR-18 with `Horde\OAuth\Client\AuthenticatedHttpClient` | Facebook client accepts a plain PSR-18. Auth wrapper is external. |
| `Horde_Service_Facebook_Auth::getLoggedInUser()` | `FacebookApiClient::getMe()` | Returns a typed `V25\Value\User` instead of an id string. |
| `Horde_Service_Facebook_Auth::revokeExtendedPermission($p)` | `FacebookApiClient::revokePermission($p)` | Method name matches Meta's actual endpoint. |
| `Horde_Service_Facebook_Users::getInfo($uid, $fields)` | `FacebookApiClient::getMe($fields)` for self. No general user lookup. | Meta gutted third-party user reads. See [MISSING_FEATURES.md](MISSING_FEATURES.md). |
| `Horde_Service_Facebook_Users::getProfileLink($uid)` | `Horde\Service\Facebook\Graph\ProfileUrls::profileLink($uid)` | Pure URL builder, no API call. |
| `Horde_Service_Facebook_Users::getThumbnail($uid)` | `Horde\Service\Facebook\Graph\ProfileUrls::thumbnailUrl($uid)` | Pure URL builder. Accepts optional size/type parameters. |
| `Horde_Service_Facebook_Users::getAppPermissions()` (self) | `FacebookApiClient::listMyPermissions()` | Third-party permissions read (other uids) has no equivalent by design. |
| `Horde_Service_Facebook_Events::get($opts)` | `FacebookApiClient::listMyUpcomingEvents()` | Legacy used FQL. Modern uses `/me/events`. Returns a lazy `PagedIterator<Event>`. |
| `Horde_Service_Facebook_Events::getMembers($eid)` | no equivalent | `/invited` edge was removed for most apps. |
| `Horde_Service_Facebook_Events::rsvp($eid, $status)` | no equivalent | Removed for public apps. |
| `Horde_Service_Facebook_Events::create($uid, $info)` | no equivalent | User event creation is gone. |
| `Horde_Service_Facebook_Streams::*` | no equivalent | News Feed / user timeline is dead. |
| `Horde_Service_Facebook_Photos::*` | no equivalent (page photos are a separate future scope) | User-photo write API is removed. |
| `Horde_Service_Facebook_Friends::*` | no equivalent | App-installed-friends only. Even that is App Review gated. |
| `Horde_Service_Facebook_Groups::*` | no equivalent | Groups API sign-off gated. Not in scope. |
| `Horde_Service_Facebook_Fql::*` | no equivalent | FQL retired 2016. |
| `Horde_Service_Facebook_Notes::*` | no equivalent | Notes product retired. |
| `Horde_Service_Facebook_Notifications::*` | no equivalent | Retired with the REST API. |
| `Horde_Service_Facebook_ErrorCodes::API_EC_*` | `Horde\Service\Facebook\Graph\GraphError` predicates | REST error taxonomy is gone. Graph has its own smaller set. Use `isAuth()`, `isTokenExpired()`, `isRateLimit()`, `isPermissionMissing()` and the rest. |
| `Horde_Service_Facebook_Exception` | `Horde\Service\Facebook\FacebookApiException` + `Horde\Service\Facebook\Graph\GraphErrorException` | Extends `HordeRuntimeException`. Graph errors carry a `GraphError` value object via `->error()`. |

The modern methods return **typed value objects**, not associative
arrays. Callers reading `$user['name']` become `$user->name` (public
readonly property) or `$user->name()` (interface accessor for
version-portable code).

## Concrete migration examples

### Fetching the current user

**Legacy:**

```php
$fb = new Horde_Service_Facebook($appId, $secret, [
    'http_client' => $httpClient,
]);
$fb->auth->setSession($accessToken);
$uid = $fb->auth->getLoggedInUser();
$info = $fb->users->getInfo($uid, ['name', 'email']);
$name = $info[0]['name'];
$email = $info[0]['email'];
```

**Modern:**

```php
use Horde\OAuth\Client\AuthenticatedHttpClient;
use Horde\OAuth\Client\TokenSet;
use Horde\Service\Facebook\FacebookApiClient;

$authClient = new AuthenticatedHttpClient($httpClient, new TokenSet(
    accessToken: $accessToken,
    tokenType: 'Bearer',
    expiresAt: null,
    refreshToken: null,
    scope: null,
));

$fb = FacebookApiClient::create($authClient, new RequestFactory());
$user = $fb->getMe(['id', 'name', 'email']);
$name = $user->name;       // public readonly property
$email = $user->email;      // ?string, null when the scope was not granted
```

### Listing the user's events

**Legacy:**

```php
$events = $fb->events->get([
    'uid' => $uid,
    'start_time' => $startTs,
    'end_time' => $endTs,
]);
// $events is an array of associative arrays with FQL-shaped keys:
// eid, name, start_time (unix timestamp), pic_square, etc.
foreach ($events as $row) {
    $name = $row['name'];
    $ts = $row['start_time'];  // int
}
```

**Modern:**

```php
use Horde\Service\Facebook\Graph\Pagination\Cursor;

// No server-side date filter. Meta gutted that. Filter client-side.
$events = [];
foreach ($fb->listMyUpcomingEvents(new Cursor(limit: 50)) as $event) {
    if ($event->startTime < $startInstant || $event->startTime > $endInstant) {
        continue;
    }
    $events[] = $event;
}

// $event is a typed V25\Value\Event with DateTimeImmutable start/end.
foreach ($events as $event) {
    $name = $event->name;
    $ts = $event->startTime;    // DateTimeImmutable
    $rsvp = $event->rsvpStatus; // ?string
}
```

Note two behaviour changes on Meta's side:

1. `start_time` / `end_time` filters at the request level are
   ineffective. Meta returns whatever it wants within its own default
   window. Filter in the caller.
2. The `pic_square` field on the FQL result is gone. Cover photos are
   on the `cover.source` field, exposed here as `$event->coverPhotoUrl`.

### Handling errors

**Legacy.** Error codes came from a 200+ constant enum:

```php
try {
    $info = $fb->users->getInfo($uid, ['name']);
} catch (Horde_Service_Facebook_Exception $e) {
    if ($e->getCode() === Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY) {
        // Re-auth.
    }
}
```

**Modern.** Predicates on the `GraphError` value object:

```php
use Horde\Service\Facebook\Graph\GraphErrorException;

try {
    $user = $fb->getMe(['name']);
} catch (GraphErrorException $e) {
    if ($e->error()->isTokenExpired()) {
        // Refresh the token.
    }
    if ($e->error()->isPermissionMissing()) {
        // Prompt for scope grant.
    }
    if ($e->error()->isRateLimit()) {
        // Back off.
    }
    throw $e;
}
```

Do not port `Horde_Service_Facebook_ErrorCodes::API_EC_*` constants
forward. The Graph API error taxonomy is completely different. A
smaller set of codes with subcodes for finer distinctions. The
predicate accessors on `GraphError` cover the cases callers actually
branch on. If you need one that isn't there, file it. Do not
string-match `getMessage()`.

## Version pinning

The legacy client had no version awareness. It called Facebook's
unversioned endpoints and got whatever Meta was routing as "no version"
(typically an old and quietly deprecated version). This was a big part
of why it stopped working.

The modern client is version-explicit at every request. Default is
`GraphApiVersion::V25_0` (currently the highest we ship). To pin:

```php
use Horde\Service\Facebook\Graph\GraphApiVersion;

$fbV24 = $fb->withVersion(GraphApiVersion::V24_0);
```

`withVersion()` returns a new immutable client. See
[VERSIONING.md](VERSIONING.md) for how we manage the supported version
window and the difference between "the library ships this version" and
"Meta still serves this version".

## OAuth2 wiring

The legacy client conflated OAuth2 code exchange, token storage and
API call issuance. The modern client separates them:

- **Authorization** and **token exchange** are `horde/oauth`'s concern.
  The Facebook package provides `FacebookProviderConfig::forVersion($v)`
  returning a pre-populated `Horde\OAuth\Client\ProviderConfig`. The
  actual flow uses `Horde\OAuth\Client\OAuth2Client`.
- **Token storage** is the caller's concern. `TokenSet` is a plain data
  holder. Where you persist it (session, database, Redis) is up to the
  application, not the library.
- **Authenticated API calls** happen through a PSR-18 client wrapped by
  `Horde\OAuth\Client\AuthenticatedHttpClient`. The Facebook client
  accepts the wrapped client without knowing about tokens at all.

See the README's OAuth2 section for a runnable example.

## What Horde applications need to change

The legacy client was surfaced as a Horde DI factory
(`Horde_Service_Facebook_Factory`) and consumed via
`$GLOBALS['injector']->getInstance('Horde_Service_Facebook')`. The
modern client has no factory in the library. DI wiring is a consumer
concern.

### For an application using the client (e.g. timeobjects)

1. Add a DI binder in the application (`app/lib/Bindings.php` or the
   modern equivalent) that constructs a `FacebookApiClient` from
   injected PSR-18, PSR-17 and an OAuth2-obtained `TokenSet`.
2. Replace
   `global $injector; $injector->getInstance('Horde_Service_Facebook')`
   with constructor injection of `FacebookApiClient`.
3. Store tokens as OAuth2 `TokenSet` values, not the legacy
   `unserialize($prefs->getValue('facebook'))` blob. That shape is
   undefined and fragile and predates OAuth2.

The Facebook client itself ships no `Factory` classes. That is
deliberate. Framework glue is not a library concern.

## Horde/core and Horde/base infrastructure concerns

Some legacy touch points sat outside the client library itself, in
`horde/core` and `horde/base` and are relevant to teams migrating a
whole application. None of these are the client's responsibility. The
client cannot fix any of them. Listed here so nothing gets missed at
deployment time.

### Preferences storage of Facebook credentials

Legacy `horde/base` had an application-level pattern of storing
`serialize()`d Facebook credential state in a user preference. That
pattern is undefined at the shape level, fragile against Facebook-side
breaking changes and predates OAuth2 entirely.

**Modern approach.** Store `TokenSet` values via a typed serialiser.
JSON is fine. `json_decode()` plus `TokenSet::__construct()` on read.
Do not `unserialize()` cross-version data. If your `horde/base`
deployment still carries legacy pref blobs, either migrate them at read
time (parse, extract the access token, discard the rest) or expire
them and force a re-authorization.

### Identity model and Facebook Login

If you use Facebook Login as an identity source (as opposed to just an
API credential source), the mapping between a Facebook user and a
local Horde account belongs in `horde/base`'s identity layer, not in
`horde/service_facebook`. This library provides the verification
primitives (`FacebookOidcSupport::verifyIdToken`). `horde/base`
provides the "map verified subject to local account" surface. See
[MISSING_FEATURES.md](MISSING_FEATURES.md) for a fuller inventory of
what "Log in with Facebook" requires beyond this library.

### Registry entries and application wiring

Legacy `horde/core` had registry entries advertising Facebook features
(the timeobjects Facebook category, various admin UIs). Those entries
reference the old `Horde_Service_Facebook` symbol. When migrating:

- If the feature still makes sense against the MVP surface, rewrite
  the registry entry to point at the new `FacebookApiClient`-backed
  implementation.
- If the feature depended on retired Facebook capabilities (photo
  tagging, friend lists, News Feed), remove the registry entry
  entirely rather than shim it. Silent no-ops are worse than an
  honest absence.
0
### CI and dependency graphs

If your application's `.horde.yml` lists `service_facebook` as a
`suggest`, verify the minimum version constraint. The `3.x` line drops
PHP 7 support and changes the required-dependency shape substantially.
No more `horde/support`, `horde/util`, `horde/translation`,
`ext-SimpleXML`. New requirements on `horde/oauth`, `horde/jwt`, PSR
HTTP contracts.

### `horde/core`'s HTTP client factory

`horde/core` provides application-level factories for
`Horde_Http_Client` (the legacy client). The modern Facebook client
accepts PSR-18 and does not care which factory produced it. If your
application wires everything through `horde/core`'s factory, wrap that
output with `horde/http`'s PSR-18 adapter before handing it to
`FacebookApiClient::create()`.

### Session-scoped token caching

Legacy code cached the OAuth "session key" on the client instance.
Modern code holds `TokenSet` values externally. Typically via
`HordeSession` scoped storage for a browser-driven flow or via the
application's token-storage service for offline flows. New code should
use `HordeSession::getScoped()` / `HordeSession::setScoped()` rather
than direct `Horde_Session` or `$_SESSION` access, which are not
appropriate for modern token storage.

## Not fixed by this migration

Explicit non-goals of the port and therefore of this upgrade path:

- The library does not offer a compatibility shim for legacy call
  sites. There is no `Horde\Service\Facebook\Compat` translating old
  method names to modern ones. The two client shapes are different
  enough that a shim would be more misleading than helpful.
- The library does not translate stored FQL-shaped payloads into
  Graph-shaped value objects. If you have persisted results from the
  legacy client, migrate them at your application layer.
- The library does not carry per-version deprecation warnings against
  Facebook's schedule. See [VERSIONING.md](VERSIONING.md). That is
  deliberate.
- The library does not implement rate-limit backoff, retry policy or
  circuit-breaking. Those are transport concerns. Wrap your PSR-18
  client with the appropriate middleware.

For the full catalog of known deferrals, behaviour differences and
larger capability areas the library does not address (Login-with-Facebook
identity mapping, Pages management, Business Manager), see
[MISSING_FEATURES.md](MISSING_FEATURES.md).

# Missing Features and Known Gaps

This document catalogs everything the MVP does not do, split into three
buckets:

1. **Known deferrals**. Features on the roadmap but not yet built.
2. **Behaviour differences** vs. the legacy client that a migrator should
   know about.
3. **Broader Facebook capability areas** the library does not address at
   all, with an honest description of what would be involved to add each.

If you are migrating from the legacy `Horde_Service_Facebook_*` client,
read this alongside [UPGRADING.md](UPGRADING.md). That document covers
the direct method-to-method migration. This one covers the
capability-level gaps.

If you are picking the library up fresh in 2026, this document tells you
what you will not get out of the box.

---

## 1. Known deferrals

Features explicitly out of scope for the MVP but valid future work when a
concrete consumer requests them.

### Long-lived token exchange

Meta issues short-lived user access tokens from the standard OAuth2 code
exchange (~1–2 hours). A separate endpoint,
`GET /oauth/access_token?grant_type=fb_exchange_token`, converts a
short-lived user token into a long-lived one (~60 days). This is
Facebook-specific. OAuth2 refresh tokens are the standard mechanism
elsewhere. Meta does not ship refresh tokens for user Login. The
fb_exchange_token grant is the closest analog.

**Status:** not implemented. Callers who need long-lived tokens must
currently call the exchange endpoint themselves via their PSR-18 client.

**When to build:** first caller that runs a background job needing a
Facebook token to survive user session boundaries. The implementation is
straightforward. A `LongLivedTokenExchange` service in `src/OAuth/` that
takes a short-lived `TokenSet` and returns a long-lived one. The right
shape depends on whether the caller wants to plug it into
`horde/oauth`'s `TokenRefresher` (which was designed for standard OAuth2
refresh flow) or run it as a separate one-shot.

### Batch requests

Meta's Graph API accepts batch requests via `POST /?batch=[...]`. One HTTP
round trip that carries multiple sub-operations and returns their
responses as a single JSON array. Legitimate performance optimization for
callers that fetch dependent resources.

**Status:** not implemented. Every method makes one HTTP round trip.

**When to build:** first caller with a documented latency problem the
batch endpoint would solve. Batching adds real complexity (error handling
per sub-request, response ordering, mixed success/failure) and should not
land until it earns its weight.

### App access token construction

Several endpoints (notably `/debug_token`) require an "app access token"
authenticating the caller as the app itself, not as a user. Meta
documents two forms:

- `"{$appId}|{$appSecret}"`. The concatenated form, safe to use on the
  server side only. Embedding the app secret in a client would be a leak.
- A dedicated `GET /oauth/access_token?client_id=X&client_secret=Y&grant_type=client_credentials`
  call that returns a real minted token.

The concatenated form works today. The modern client requires the caller
to build the string themselves when calling `debugToken()`. The
convenience helper `FacebookApiClient::appAccessToken(string $appId, string $appSecret): string`
is a plausible addition. The minted-token flow could ship next to it.

**Status:** concatenated form works via caller string interpolation.
Minted-token flow not implemented.

**When to build:** low priority. Both idioms are one line at the call
site. A README example is probably sufficient.

### Unversioned Graph endpoints

The URL builder always prepends a version segment. Meta accepts a small
number of endpoints without a version segment. Notably `/oauth/access_token`
at the initial app-token minting stage and `/{uid}/picture` (see the
`ProfileUrls` helper).

For `ProfileUrls::thumbnailUrl()` the modern client passes a version
anyway. Meta serves the same content either way. For the OAuth2 flow,
`horde/oauth` handles endpoint URLs directly through the `ProviderConfig`
and does not route through our URL builder.

**Status:** not a caller-facing issue today. Documented for completeness.

---

## 2. Behaviour differences from the legacy client

Not bugs. Not deferrals. Deliberate changes callers migrating from the
legacy client should know about.

### No implicit in-memory caching

The legacy `Users` module cached `getInfo($uid, $fields)` results by
`md5($uid . implode(',', $fields))` inside the module for the lifetime
of the process. Two identical calls in the same request cycle hit the
wire once.

Modern `FacebookApiClient::getMe($fields)` has no such cache. Every call
hits the wire.

**Why:** cache policy is a transport concern. The library accepts any
PSR-18 client. Callers who want caching wrap their client with an HTTP
cache middleware. `horde/http` supports response-header caching. Guzzle
has a cache middleware. Symfony HttpClient has `CachingHttpClient`. The
library embedding its own in-memory cache would silently interact with
any HTTP-level cache the caller configured, which is worse than being
explicit.

**Migration:** if you rely on the legacy module's cache, add HTTP-level
caching to your PSR-18 wrapper. In many apps this ends up being cleaner
than the legacy behaviour because the cache also covers non-Users
requests.

### Third-party user reads are not exposed

Legacy `Users::getInfo($uid, $fields)` accepted **any uid**. Modern
`FacebookApiClient::getMe($fields)` only serves the token holder's own
profile.

**Why:** Meta gutted third-party user reads years ago. Calling
`GET /{other_uid}?fields=name` returns essentially empty responses
unless the caller has an unusual scope grant, which for general library
consumers they will not. The modern client not exposing the operation
prevents callers from writing code that "works" but returns nothing
useful in production.

**Migration:** if you actually need arbitrary user reads and have the
scope for them, call your PSR-18 client directly against
`/v{N}.{n}/{uid}?fields=...` using the request factory. The
`GraphResponseDecoder` handles the response either way. The library
does not offer a facade method because the operation is not portable
across normal app configurations.

### Errors carry a value object, not a code constant

Legacy code branched on `Horde_Service_Facebook_ErrorCodes::API_EC_*`
constants. 200+ REST-era codes. That taxonomy is gone. Modern code
branches on predicates of the `GraphError` value object retrievable via
`GraphErrorException::error()`. See [UPGRADING.md](UPGRADING.md) for the
call-site rewrite.

### Return types are typed value objects, not arrays

Legacy code read `$user['name']`. Modern code reads `$user->name`
(concrete class) or `$user->name()` (shared interface). This is a shape
change every migrating caller has to make.

---

## 3. Broader Facebook capability areas not covered

The MVP targets the narrow surface `horde/service_facebook`'s one
internal consumer (timeobjects) needs plus the OAuth2/OIDC verification
primitives strategy work depends on. Everything else is a valid future
addition. None of it is on any current roadmap.

Three commonly-requested capability areas are called out below so callers
considering the library know what they will not get.

### "Log in with Facebook" as an identity provider

**What it means:** using Facebook Login as an authentication source for
a Horde-hosted application. A user clicks "Sign in with Facebook", grants
OAuth consent, and the application creates or resolves a local Horde
account bound to the Facebook `sub` claim.

**What the library ships today:**

- `FacebookProviderConfig::forVersion($v)`. The OAuth2/OIDC endpoint
  configuration ready to plug into `Horde\OAuth\Client\OAuth2Client`.
- `FacebookOidcSupport::verifyIdToken($jwt, $options)`. Cryptographic
  verification of a Facebook-issued OIDC `id_token`, returning a
  `VerifiedToken` carrying the `sub`, `aud`, `iss`, `exp`, `iat` claims.
- `FacebookApiClient::getMe(['id', 'name', 'email'])`. Post-login profile
  fetch to populate the local account.

**What is missing to make it end-to-end:**

- **Identity mapping.** A verified `sub` claim from Meta is a Facebook
  user identifier. Creating an Identity or associating it with an existing one, refusing login for
  banned users is application logic that belongs in `horde/base`, not
  in this library. This library provides the primitives. The
  identity binder is separate.
- **Session establishment.** After successful verification the
  application needs to establish a Horde session, JWT-backed or
  cookie-backed. Not this library's concern.
- **CSRF / state protection for the OAuth redirect.** The OAuth2 flow
  requires the caller to generate a random `state` value on the
  authorize redirect and verify it on the callback. `horde/oauth`
  provides `PkceGenerator` and `OAuthFlowStore` for related concerns.
  The identity layer wires them into the login form.
- **Nonce protection for id_token replay.** The `openid` scope produces
  an `id_token` that should carry a `nonce` claim matching a random
  value the caller stored between authorize and callback.
  `verifyIdToken()` does not check nonces. The caller does, because the
  nonce lives in session-scoped storage the library does not own.
- **"Which local identity does this Facebook user map to" persistence.** A
  mapping table (Facebook `sub` to local user id) somewhere the
  application can query. Schema, storage backend, access pattern are
  all application choices.

### Managing Facebook Pages

**What it means:** operations against Pages (business/brand accounts a
user administers on Facebook). Reading page insights, publishing posts
to a page's timeline, managing page comments, listing page events,
reading page metadata.


**What is missing to add Pages support:**

- **The `pages_*` scope family.** Meta gates every page operation
  behind a scope from the `pages_*` family. `pages_read_engagement`,
  `pages_manage_posts`, `pages_manage_metadata`,
  `pages_read_user_content`, `pages_manage_engagement`,
  `pages_show_list`, `pages_messaging`, and more. Each requires **App
  Review**. Meta reviews the app's use case and privacy policy before
  granting production access. Some scopes also require
  business verification. This is a policy gate, not a code gate, but
  it materially shapes what a library can offer. Any Pages
  functionality only works if the consuming application has passed
  App Review for the relevant scopes.
- **Page access tokens.** Distinct from user access tokens. A user
  token scoped to `pages_show_list` can list the pages a user
  administers. Each page has its own separate token used for
  operations on that page. This is a different token lifecycle than
  what the MVP models. The library currently assumes one token per
  client instance. Pages require token swapping at request time.
- **Endpoint modules.** Each Page capability is a separate endpoint
  family. `GET /{page-id}` for metadata. `POST /{page-id}/feed` for
  publishing. `GET /{page-id}/insights` for analytics (a mini-DSL of
  its own with metric names, period aggregations, date ranges).
  `GET /{page-id}/events`. `GET /{page-id}/posts`. And so on. A
  substantial surface. Realistically another MVP worth of work.
- **Value objects.** `Page`, `PagePost`, `PageInsight`, `PageComment`,
  each in `V{N}\Value\` following the existing shape. Shared
  interfaces in `Graph\Value\` for cross-version portability.
- **Page-scoped facade methods** on `FacebookApiClient`. Or, more
  likely given the surface size, a sub-facade obtained via
  `$fb->pages()->manage($pageId)` returning a page-scoped client.

**Rough scope:** comparable to the entire user-centric MVP that already
exists. Not "add a method or two". A whole additional MVP with its own
scoping decisions, its own value-object trees, its own App Review
dependencies.

### Meta Business Manager

**What it means:** the platform Meta operates for agencies, larger
brands and businesses that manage multiple Facebook Pages, Instagram
accounts, ad accounts and system users under a shared organizational
umbrella. The API surface is `/business/*`. Reading business metadata,
provisioning system users, listing owned pages and ad accounts,
managing business asset groups, delegating access to partners.

**What the library ships today:**

- Nothing. Business Manager is not in scope for the MVP or for any of
  the deferred features above.

**What is missing:**

- **A different auth model.** Business Manager operations run under
  **system user tokens**. Long-lived tokens minted by Business Manager
  itself for automated use, not user-delegated tokens from a Login
  flow. Provisioning a system user is a Business Manager UI operation.
  The token that comes out is what an automation client uses. The
  MVP's user-Login-centric OAuth2 wiring does not apply.
- **Access approval processes.** Meta's Business Manager API is behind
  a separate approval track from the standard app review. Access is
  granted per-business, per-partner, per-scope in Meta's business
  admin UI. A library shipping this functionality does not enable it.
  It merely provides the client for callers whose businesses have
  already been granted the access.
- **A different endpoint surface.** `/{business-id}/owned_pages`,
  `/{business-id}/system_users`, `/{business-id}/pending_users`,
  `/{business-id}/business_users`, and so on. Value objects for
  `Business`, `SystemUser`, `BusinessRole`, `PartnerBusiness`, and the
  rest.
- **Multi-tenant token management.** A real Business Manager consumer
  juggles many tokens. One per business, one per system user, one per
  Page owned by the business. The MVP's "one client, one version, one
  token" shape does not fit. A Business Manager surface would need a
  richer client construction pattern.

**Rough scope:** a separate library entirely
(`horde/service_facebook_business` or similar), building on this one
as a base for URL construction, error mapping, and value-object
hydration. Not something to add to `horde/service_facebook` proper.
The surface is too large and the auth model too different.

**When to build:** if and only if a caller with an actual Business
Manager approval and a concrete use case appears. The library should
not ship speculative Business Manager support without a concrete
consumer, because most of the value comes from getting the auth and
error-mapping details right against Meta's real behaviour, which
requires live access to a real business account.

---

## 4. What this document does NOT list

For completeness, three categories of "missing" that are permanent and not gaps:

- **The retired REST API, FQL, News Feed reading, user-timeline writes,
  photo tagging, Notes, Notifications.** These endpoints do not exist
  in modern Facebook. See [UPGRADING.md](UPGRADING.md) for the full
  list.
- **Rate-limit backoff, retry policy, circuit breaking.** Transport
  concerns. Wrap your PSR-18 client with the appropriate middleware.
- **Cache policy.** Same reasoning. HTTP-level, not library-level.

---

## 5. Reporting new gaps

If you find something the legacy client did that a modern equivalent
should exist for, and this document does not list it as a known gap
or a Facebook-side retirement, file it. Missing capability additions
are exactly the kind of small PRs the library is set up to accept.

Please do NOT file requests for:

- Endpoints Meta has retired (nothing we can do).
- Endpoints behind Meta capability approvals your app has not passed
  (add them yourself once approval lands, PR the general shape once
  it works).
- Speculative Business Manager or Pages methods without a concrete
  running use case (see the "when to build" notes in each section).

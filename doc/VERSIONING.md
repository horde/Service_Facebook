# Versioning

Two different version numbers matter for this package. They evolve
independently. Callers should be clear about which one they mean when
they talk about "the version".

1. **The library's semver version.** `1.x`, `2.x`, `3.x`, `4.x`. Managed
   by `horde-components release` from commit messages. This tracks *our*
   API contract to consumers of `horde/service_facebook`.
2. **The Facebook Graph API version.** `v22.0`, `v23.0`, `v24.0`,
   `v25.0`, and so on. Expressed by the
   `Horde\Service\Facebook\Graph\GraphApiVersion` enum. This tracks
   *Meta's* API contract to us.

Confusing the two produces bad decisions. Meta shipping v26 does not
oblige us to publish a new major. Us dropping support for v22 does
oblige us to publish a new major. This document spells out both
directions.

## How the Facebook Graph API version works

Facebook typically ships a new major Graph version approximately every
three to four months. Each version is a fixed URL segment (`/v25.0/...`)
and comes with its own field set, its own deprecations, its own error
codes. Meta supports several versions concurrently on a rolling window,
usually three to four majors, and hard-deprecates the oldest when a new
one ships.

Consequences:

- On any given day, calling `/v22.0/me/events` and `/v25.0/me/events`
  are legitimately different operations returning different response
  shapes.
- Meta's version support is a moving target. What worked last quarter
  might not work this quarter, even against the same URL.
- Pinning to an old version is the correct behaviour for callers who
  have code depending on that version's exact shape. Sliding to the
  newest version by default is the correct behaviour for callers who
  want current features.

The library treats **version as a first-class request-time concern**.
The default client targets the highest version we ship. Callers who
need to pin call `withVersion(GraphApiVersion::V24_0)` and get a new
immutable client locked to that version.

## The two lifecycles

The library separates two things Meta conflates:

### Lifecycle A: the enum-case window (what the library will send)

The set of `GraphApiVersion` cases the library will construct URLs for.
Every case here represents a version whose request/response shape our
code handles cleanly. Currently:

- `V25_0` (default)
- `V24_0`
- `V23_0`
- `V22_0`

Whether Meta still accepts a request on any of these is Meta's business.
The library **does not precompute** Meta's schedule. If Meta rejects a
request on `v22.0`, callers get a `GraphErrorException` like any other
server error. No different from Meta rejecting an authenticated request
because the token expired. The client's only claim is: "these are the
versions I can construct."

### Lifecycle B: the in-tree code (what the library can hydrate)

The set of `Graph/Endpoint/V{N}/` subtrees and value objects that live
in the source tree. This is decoupled from Lifecycle A. Reasons to keep
an in-tree subtree after its enum case is removed:

- Downstream applications have already-retrieved v22 artifacts stored
  in a database, cache, or archive. They still need
  `Graph\Endpoint\V22\Value\Event::fromRawResponse($storedJson)` to
  hydrate old payloads into typed objects.
- Migration tooling reads a v22 payload and rewrites it into v25 shape.
  That tool imports both value-object trees at once.
- Debug/audit surfaces render historical records captured on v22.

The value objects are **pure data holders**. They construct from decoded
JSON via `::fromApiResponse(object $data): self` and have no dependency
on the HTTP client, the config, or the version enum. That is exactly
what makes them useful long after wire retirement.

## What we do when Facebook ships a new version (feature release)

Meta ships `v26.0`. The library's response:

1. **Add the enum case.** `case V26_0 = 'v26.0';` in
   `src/Graph/GraphApiVersion.php`. One line.
2. **Add a V26 subtree if any endpoint shape drifted.** For each
   endpoint whose response shape changed from v25 to v26, add
   `src/Graph/Endpoint/V26/{Endpoint}.php` overriding just that
   endpoint's hydrator. If the shape is identical to v25 (the common
   case), no subtree is needed. The `EndpointResolver`'s `match` block
   falls through to V25 with the URL builder pinned to v26.
3. **Add per-version value objects for genuinely-drifted resources.**
   If `V26\Value\Event` genuinely has a different field surface from
   `V25\Value\Event`, ship a new value-object class. Otherwise reuse
   V25.
4. **Consider bumping the default.** `GraphApiVersion::default()`
   returns the version new clients target when the caller does not
   specify one. Bumping it to `V26_0` is a policy call. Typically we
   wait a few weeks after Meta's release to catch any Meta-side
   regressions before making v26 the default for our consumers.
5. **Ship a minor release.** Adding a version is a backward-compatible
   feature addition on our side. Existing callers pinned to older
   versions are unaffected. A **minor version bump** of the library.
   `4.1.0`, `4.2.0`, and so on.

The changelog entry is the commit message. See the CLAUDE.md project
rules on `doc/changelog.yml` being release-tool-generated from commits.

## What we do when Facebook retires an old version (wire retirement)

Meta announces `v22.0` will be retired on a specific date. Live wire
calls against `v22.0` start returning errors on or after that date. The
library's response:

**Nothing immediately.** We do not remove the enum case. We do not
touch the code. Callers still holding v22-pinned clients get server
errors from Meta at request time. That is fine. That is what the
`GraphErrorException` error taxonomy is for. Callers who need to
migrate off v22 update their pin at their own pace.

The library remains useful for the entire post-retirement period
because:

- Callers who have persisted v22 response payloads can still hydrate
  them into `V22\Value\Event` objects via `::fromRawResponse()`.
- Callers running against a Meta-mirroring test double (a Meta replay
  fixture, a captured integration recording) can still exercise v22
  code paths.
- Migration and audit tooling can walk historical records.

We remove the enum case only when **we decide** the maintenance cost
outweighs the compatibility benefit. That is a library-side call, made
deliberately.

## Removing an enum case (breaking change, our schedule)

Deciding to stop supporting `withVersion(GraphApiVersion::V22_0)`:

1. **Announce.** Mark `case V22_0` with a `@deprecated` docblock one
   minor release in advance. Existing callers get IDE warnings.
2. **Remove.** Delete `case V22_0` from `GraphApiVersion`. Delete the
   `V22_0` arms from `EndpointResolver`'s `match` blocks.
3. **Bump the major.** Removing an enum case is a breaking change for
   any consumer who pinned to it. The next release is a **major
   version bump** of the library. `4.x` becomes `5.x`.

The value-object subtree at `Graph/Endpoint/V22/Value/*.php` **may
remain in the tree** after the enum case is gone. Callers can still
construct `V22\Value\Event` via `new` and `::fromRawResponse()` for
hydrating stored data. They just cannot ask the client to *send* a
request on v22 anymore. This is the intended split.

## Removing an in-tree subtree (also breaking, also our schedule)

Deciding to drop the V22 value-object subtree entirely:

1. **Announce.** `@deprecated` docblocks on every V22 class one minor
   release in advance.
2. **Remove.** Delete `src/Graph/Endpoint/V22/` and all files under it.
3. **Bump the major.** Any consumer who imports those types breaks.

Because value-object subtrees can outlive their enum case by years,
this cleanup usually happens well after enum-case removal. Both
operations are independent semver-major events on our schedule.
Neither is Meta's schedule.

## Concrete example timeline

An illustrative timeline showing how the two version numbers evolve
independently:

| Date | Meta ships | Library ships | Library version | Notes |
|---|---|---|---|---|
| 2026-Q1 | v25.0 GA | (no change, already supported at 4.0.0) | 4.0.0 | Baseline. |
| 2026-Q2 | v26.0 GA | Add `V26_0` enum + subtree | 4.1.0 | Minor. Existing callers unaffected. |
| 2026-Q3 | (announces v22 retirement for 2026-Q4) | (no change) | 4.1.0 | Library ignores announcement. Callers pinned to v22 can still send requests. Meta will respond. |
| 2026-Q4 | v22 wire-retired | (no change) | 4.1.0 | Callers pinned to v22 start getting `GraphErrorException` at request time. Hydration of stored v22 payloads still works. |
| 2027-Q1 | v27.0 GA | Add `V27_0` enum, deprecate `V22_0` case | 4.2.0 | Minor. `V22_0` gets `@deprecated`. |
| 2027-Q2 | | Remove `V22_0` enum case | 5.0.0 | **Major.** Consumers who pinned to `V22_0` at compile time break. Value-object subtree kept for hydration. |
| 2028-Q1 | | Deprecate `V22\Value\*` subtree | 5.1.0 | Minor. IDE warnings on the value-object imports. |
| 2028-Q2 | | Remove `V22\Value\*` subtree | 6.0.0 | **Major.** Consumers importing V22 value objects break. |

Every column moves at its own pace, driven by different pressures. The
library's major version is bound to compat breaks in our own API, not
to Meta's schedule.

## What we deliberately do not do

- **We do not embed Meta's deprecation dates in code.** No
  `isDeprecated()` predicate on `GraphApiVersion`. No
  `deprecationDate()` method. No YAML version catalog with
  release/retirement metadata. That was in an earlier draft of the
  design and was rejected: Meta's schedule is not the library's
  concern.
- **We do not automatically log warnings when a caller uses a version
  Meta has retired.** If Meta returns an error, callers see the error.
  That is enough signal.
- **We do not preemptively delete enum cases based on Meta's
  schedule.** We keep them until we decide the maintenance is not
  worth the compatibility benefit. Often long after Meta stops
  accepting the wire calls.
- **We do not add new versions preemptively.** `V27_0` is added to the
  enum when Meta ships v27, not before. Callers who need to build
  against an unreleased version can subclass the URL builder locally.
  The library does not ship speculative support.

## What consumers should do

- **Default clients target `GraphApiVersion::default()`.** Do not pass
  a version unless you have a specific reason to. Following the
  default lets you inherit our version-bump decisions when we make
  them.
- **Pin explicitly if you depend on a version's exact shape.**
  `withVersion(GraphApiVersion::V25_0)` documents the assumption. If
  we later change the default to `V26_0` your code still runs on v25.
- **Hydrate stored payloads with per-version value objects.** A
  payload captured under v22 hydrates via
  `V22\Value\Event::fromRawResponse($json)` regardless of what your
  current client's live version pin is.
- **Do not string-match Meta's error messages** to detect version
  retirement. Meta rewords errors without notice. Use the `GraphError`
  predicates on the exception.

## See also

- [UPGRADING.md](UPGRADING.md). Migration from the legacy PSR-0
  client.

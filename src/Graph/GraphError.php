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
 * Immutable value object over a Meta Graph API error envelope.
 *
 * A typical error response body looks like:
 *
 *   { "error": {
 *       "message": "Invalid OAuth access token.",
 *       "type": "OAuthException",
 *       "code": 190,
 *       "error_subcode": 460,
 *       "fbtrace_id": "AbCdEfGh"
 *   } }
 *
 * This class carries those fields verbatim and exposes semantic
 * predicates over the code/subcode combinations Meta documents. The
 * predicate names are drawn from Meta's error handling guide and the
 * few common subcodes callers actually branch on in practice:
 *
 *   isAuth()             — OAuthException family (code 190)
 *   isTokenExpired()     — code 190 with subcode 463 or 467
 *   isRateLimit()        — code 4, 17, 32, or 613 (application, user,
 *                          page, or custom rate limit)
 *   isAppRateLimit()     — code 4 only
 *   isUserRateLimit()    — code 17 only
 *   isPermissionMissing() — code 200 (permission not granted)
 *   isTransient()        — code 1 or 2 (unknown/temporary error)
 *
 * Callers should never string-match Meta's `message` field. Meta
 * reworps that text without notice; use the predicates instead. If a
 * caller needs a distinction the predicates don't expose, add a
 * predicate — do not push the string-match into caller code.
 *
 * fbtrace_id is a request identifier Meta uses in support tickets.
 * Log it alongside every error the client cannot recover from
 * automatically.
 */
final class GraphError
{
    public function __construct(
        public readonly string $message,
        public readonly string $type,
        public readonly int $code,
        public readonly ?int $subcode,
        public readonly ?string $traceId,
        public readonly ?string $userTitle = null,
        public readonly ?string $userMsg = null,
    ) {}

    /**
     * Construct a GraphError from Meta's JSON error envelope.
     *
     * $json must be the decoded error object (the inner object, i.e.
     * $decodedBody->error). If a caller has the outer response object,
     * they should extract $decodedBody->error before calling.
     *
     * Missing fields default to sensible values rather than throwing —
     * Meta occasionally omits `error_subcode`, and older versions do
     * not always emit `fbtrace_id`. A malformed envelope entirely
     * (e.g. no message field) is caller's problem to detect via the
     * decoder, not here.
     */
    public static function fromApiResponse(object $json): self
    {
        return new self(
            message: (string) ($json->message ?? ''),
            type: (string) ($json->type ?? 'Unknown'),
            code: (int) ($json->code ?? 0),
            subcode: isset($json->error_subcode) ? (int) $json->error_subcode : null,
            traceId: isset($json->fbtrace_id) ? (string) $json->fbtrace_id : null,
            userTitle: isset($json->error_user_title) ? (string) $json->error_user_title : null,
            userMsg: isset($json->error_user_msg) ? (string) $json->error_user_msg : null,
        );
    }

    public function isAuth(): bool
    {
        return $this->code === 190;
    }

    public function isTokenExpired(): bool
    {
        return $this->code === 190 && ($this->subcode === 463 || $this->subcode === 467);
    }

    public function isRateLimit(): bool
    {
        return in_array($this->code, [4, 17, 32, 613], true);
    }

    public function isAppRateLimit(): bool
    {
        return $this->code === 4;
    }

    public function isUserRateLimit(): bool
    {
        return $this->code === 17;
    }

    public function isPermissionMissing(): bool
    {
        return $this->code === 200;
    }

    public function isTransient(): bool
    {
        return $this->code === 1 || $this->code === 2;
    }
}

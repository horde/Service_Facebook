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

use Horde\Service\Facebook\FacebookApiException;
use Throwable;

/**
 * Exception thrown when a Graph API request returns an error envelope
 * that could be decoded into a GraphError value object.
 *
 * Callers who want to branch on Meta's error taxonomy retrieve the
 * GraphError via error() and read its semantic predicates:
 *
 *   try {
 *       $me = $fb->getMe();
 *   } catch (GraphErrorException $e) {
 *       if ($e->error()->isTokenExpired()) { ... }
 *       if ($e->error()->isRateLimit())    { ... }
 *       throw $e;
 *   }
 *
 * The exception message is Meta's error message verbatim (or a
 * fallback if the envelope was empty), so bare catch blocks that just
 * log $e->getMessage() still get something usable. The HTTP status code
 * from the response is exposed via httpStatus() for callers that need
 * to distinguish 4xx from 5xx without inspecting the error code.
 *
 * Malformed error responses — HTTP 4xx/5xx with no parseable Meta
 * envelope — throw FacebookApiException directly rather than
 * GraphErrorException, since there's no GraphError to carry.
 */
class GraphErrorException extends FacebookApiException
{
    public function __construct(
        private readonly GraphError $error,
        private readonly int $httpStatus,
        ?Throwable $previous = null,
    ) {
        parent::__construct($error->message !== '' ? $error->message : 'Graph API error', $httpStatus, $previous);
    }

    public function error(): GraphError
    {
        return $this->error;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}

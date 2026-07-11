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

namespace Horde\Service\Facebook\Graph\Request;

use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Build a GET /me request.
 *
 * Version-neutral: the URL builder prepends the active
 * GraphApiVersion segment. Callers optionally supply a list of fields
 * to include in the ?fields= query parameter; an empty list defers to
 * Meta's default projection (typically id and name only).
 */
final class GetMeRequestFactory
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly array $fields = [],
    ) {}

    public function create(): RequestInterface
    {
        $params = [];
        if ($this->fields !== []) {
            $params['fields'] = implode(',', $this->fields);
        }
        return $this->requestFactory
            ->createRequest('GET', $this->urlBuilder->build('/me', $params))
            ->withHeader('Accept', 'application/json');
    }
}

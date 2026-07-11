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
 * Build a GET /me/permissions request.
 *
 * Meta's /me/permissions endpoint is unusually cheap — no field
 * selection, no pagination in practice (permission lists are always
 * small enough to fit in one page), no scope requirements beyond a
 * valid access token. This factory produces the bare request.
 */
final class GetPermissionsRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
    ) {}

    public function create(): RequestInterface
    {
        return $this->requestFactory
            ->createRequest('GET', $this->urlBuilder->build('/me/permissions'))
            ->withHeader('Accept', 'application/json');
    }
}

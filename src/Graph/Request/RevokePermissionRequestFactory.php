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
 * Build a DELETE /me/permissions/{permission} request.
 *
 * Revokes a single previously-granted permission from the caller's
 * access token. The token itself remains valid but loses the ability
 * to access the revoked scope. Meta returns
 * {"success":true} on success and a standard error envelope on
 * failure; the endpoint layer maps both cases through the shared
 * GraphResponseDecoder.
 */
final class RevokePermissionRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly string $permission,
    ) {}

    public function create(): RequestInterface
    {
        return $this->requestFactory
            ->createRequest('DELETE', $this->urlBuilder->build('/me/permissions/' . rawurlencode($this->permission)))
            ->withHeader('Accept', 'application/json');
    }
}

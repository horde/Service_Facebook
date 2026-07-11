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

namespace Horde\Service\Facebook\Graph\Endpoint\V25;

use Horde\Service\Facebook\FacebookApiException;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Permission;
use Horde\Service\Facebook\Graph\GraphEdge;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Horde\Service\Facebook\Graph\Request\GetPermissionsRequestFactory;
use Horde\Service\Facebook\Graph\Request\RevokePermissionRequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * /me/permissions endpoint for Graph API v25.
 *
 * List and revoke permissions on the current access token. Meta
 * returns permission lists as a small unpaginated edge; this endpoint
 * eagerly materialises the full list into a plain array of Permission
 * value objects rather than exposing a PagedIterator, because there is
 * only ever one page in practice.
 */
final class PermissionsEndpoint
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly GraphResponseDecoder $decoder,
    ) {}

    /**
     * @return list<Permission>
     */
    public function listMyPermissions(): array
    {
        $factory = new GetPermissionsRequestFactory($this->requestFactory, $this->urlBuilder);
        $response = $this->httpClient->sendRequest($factory->create());
        $decoded = $this->decoder->decode($response);

        if (!$decoded instanceof GraphEdge) {
            throw new FacebookApiException(
                'GET /me/permissions returned a node shape; expected edge',
                $response->getStatusCode(),
            );
        }

        $out = [];
        foreach ($decoded->data as $raw) {
            $out[] = Permission::fromApiResponse($raw);
        }
        return $out;
    }

    /**
     * Revoke a previously-granted permission. Meta returns
     * {"success":true} on success; the shared decoder wraps that in a
     * GraphNode which we just discard. Any error condition raises
     * through the decoder as a GraphErrorException.
     */
    public function revoke(string $permission): void
    {
        $factory = new RevokePermissionRequestFactory(
            $this->requestFactory,
            $this->urlBuilder,
            $permission,
        );
        $response = $this->httpClient->sendRequest($factory->create());
        $this->decoder->decode($response);
    }
}

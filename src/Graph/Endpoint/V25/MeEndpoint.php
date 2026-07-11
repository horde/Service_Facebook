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
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\User;
use Horde\Service\Facebook\Graph\GraphNode;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Horde\Service\Facebook\Graph\Request\GetMeRequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * /me endpoint for Graph API v25.
 *
 * Fetches metadata about the user represented by the current access
 * token. The fields parameter is optional — an empty list defers to
 * Meta's default projection (id and name only). Callers who need
 * email, name breakdown, or profile link must explicitly request
 * those fields AND hold the matching scopes on their access token
 * (email requires the email scope; the name breakdown requires
 * public_profile).
 *
 * Returns a version-specific User value object. Callers who wrote
 * against the shared Horde\Service\Facebook\Graph\Value\User
 * interface see only id() and name(); callers who type-hint the
 * concrete class here get the full v25 field surface.
 */
final class MeEndpoint
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly GraphResponseDecoder $decoder,
    ) {}

    /**
     * @param list<string> $fields
     */
    public function get(array $fields = []): User
    {
        $factory = new GetMeRequestFactory($this->requestFactory, $this->urlBuilder, $fields);
        $response = $this->httpClient->sendRequest($factory->create());
        $decoded = $this->decoder->decode($response);

        if (!$decoded instanceof GraphNode) {
            throw new FacebookApiException(
                'GET /me returned an edge shape; expected node',
                $response->getStatusCode(),
            );
        }

        return User::fromApiResponse($decoded->raw);
    }
}

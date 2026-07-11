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
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\DebugTokenInfo;
use Horde\Service\Facebook\Graph\GraphNode;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Horde\Service\Facebook\Graph\Request\DebugTokenRequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * /debug_token endpoint for Graph API v25.
 *
 * Verifies an access token and returns metadata about it (validity,
 * app_id, user_id, scopes, expiry). The endpoint takes TWO tokens:
 * $inputToken is the token being examined (typically a
 * caller-supplied user or client token whose fitness the server wants
 * to verify), $appAccessToken is the caller's own app-level token
 * (usually app_id|app_secret concatenated) that authorises the
 * introspection call.
 *
 * Meta wraps the payload in a "data" key at the transport layer; the
 * endpoint unwraps that here so the value object hydrates from the
 * inner object directly.
 */
final class DebugTokenEndpoint
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly GraphResponseDecoder $decoder,
    ) {}

    public function get(string $inputToken, string $appAccessToken): DebugTokenInfo
    {
        $factory = new DebugTokenRequestFactory(
            $this->requestFactory,
            $this->urlBuilder,
            $inputToken,
            $appAccessToken,
        );
        $response = $this->httpClient->sendRequest($factory->create());
        $decoded = $this->decoder->decode($response);

        if (!$decoded instanceof GraphNode) {
            throw new FacebookApiException(
                'GET /debug_token returned an edge shape; expected node',
                $response->getStatusCode(),
            );
        }

        // Meta wraps /debug_token's payload in {"data":{...actual...}}.
        // Unwrap here so the value object hydrates from the inner
        // object cleanly.
        $raw = isset($decoded->raw->data) && is_object($decoded->raw->data)
            ? $decoded->raw->data
            : $decoded->raw;

        return DebugTokenInfo::fromApiResponse($raw);
    }
}

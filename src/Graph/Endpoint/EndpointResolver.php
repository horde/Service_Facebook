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

namespace Horde\Service\Facebook\Graph\Endpoint;

use Horde\Service\Facebook\FacebookApiConfig;
use Horde\Service\Facebook\Graph\Endpoint\V25\DebugTokenEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\EventsEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\MeEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\PermissionsEndpoint;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Selects the concrete endpoint bundle for the client's active
 * GraphApiVersion.
 *
 * The facade (FacebookApiClient) delegates each public method call to
 * a version-appropriate endpoint. This resolver is the seam. In the
 * MVP the only fully-populated subtree is V25; every enum case
 * currently falls through to the V25 endpoint classes with the URL
 * builder pinned to the requested version. This is a legitimate
 * fall-through — the endpoint code path is the same shape across
 * these versions and the URL segment is the only per-version
 * difference the URL builder needs.
 *
 * When Meta ships v26 with genuine shape drift, we add
 * src/Graph/Endpoint/V26/ overriding just the endpoints that changed,
 * extend this class with a match arm for V26_0, and the rest of the
 * facade code path stays untouched.
 *
 * Callers do not use this class directly. The facade constructs one
 * per client instance.
 */
final class EndpointResolver
{
    private readonly GraphUrlBuilder $urlBuilder;
    private readonly GraphResponseDecoder $decoder;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        FacebookApiConfig $config,
    ) {
        $this->urlBuilder = new GraphUrlBuilder($config->baseUrl, $config->version);
        $this->decoder = new GraphResponseDecoder();
    }

    public function me(GraphApiVersion $version): MeEndpoint
    {
        return match ($version) {
            GraphApiVersion::V25_0,
            GraphApiVersion::V24_0,
            GraphApiVersion::V23_0,
            GraphApiVersion::V22_0 => new MeEndpoint(
                $this->httpClient,
                $this->requestFactory,
                $this->urlBuilder,
                $this->decoder,
            ),
        };
    }

    public function events(GraphApiVersion $version): EventsEndpoint
    {
        return match ($version) {
            GraphApiVersion::V25_0,
            GraphApiVersion::V24_0,
            GraphApiVersion::V23_0,
            GraphApiVersion::V22_0 => new EventsEndpoint(
                $this->httpClient,
                $this->requestFactory,
                $this->urlBuilder,
                $this->decoder,
            ),
        };
    }

    public function permissions(GraphApiVersion $version): PermissionsEndpoint
    {
        return match ($version) {
            GraphApiVersion::V25_0,
            GraphApiVersion::V24_0,
            GraphApiVersion::V23_0,
            GraphApiVersion::V22_0 => new PermissionsEndpoint(
                $this->httpClient,
                $this->requestFactory,
                $this->urlBuilder,
                $this->decoder,
            ),
        };
    }

    public function debugToken(GraphApiVersion $version): DebugTokenEndpoint
    {
        return match ($version) {
            GraphApiVersion::V25_0,
            GraphApiVersion::V24_0,
            GraphApiVersion::V23_0,
            GraphApiVersion::V22_0 => new DebugTokenEndpoint(
                $this->httpClient,
                $this->requestFactory,
                $this->urlBuilder,
                $this->decoder,
            ),
        };
    }
}

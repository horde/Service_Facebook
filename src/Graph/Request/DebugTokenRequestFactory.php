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
 * Build a GET /debug_token?input_token=X&access_token=APP request.
 *
 * The /debug_token endpoint is a special case: authentication comes
 * from an app-level access token (client_id|client_secret) supplied
 * as a query parameter, not from the Bearer token on the request. The
 * "input token" being examined is the OTHER query parameter — the
 * user or client token whose fitness you want to verify.
 *
 * Callers who route their outbound requests through
 * AuthenticatedHttpClient still get a Bearer header attached; Meta
 * ignores it on this endpoint in favour of the query-parameter
 * access_token. That is Meta's design, not ours.
 */
final class DebugTokenRequestFactory
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly GraphUrlBuilder $urlBuilder,
        private readonly string $inputToken,
        private readonly string $appAccessToken,
    ) {}

    public function create(): RequestInterface
    {
        $url = $this->urlBuilder->build('/debug_token', [
            'input_token' => $this->inputToken,
            'access_token' => $this->appAccessToken,
        ]);
        return $this->requestFactory
            ->createRequest('GET', $url)
            ->withHeader('Accept', 'application/json');
    }
}

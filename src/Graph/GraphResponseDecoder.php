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
use Psr\Http\Message\ResponseInterface;

/**
 * Turns a PSR-7 response into a GraphNode, a GraphEdge, or an
 * exception. The single shared entry point every endpoint uses to
 * consume its response, so the error mapping, the JSON decoding, and
 * the node-vs-edge discrimination live in one place.
 *
 * Contract:
 *
 *   - HTTP 2xx with a JSON body containing top-level "data" and
 *     (optionally) "paging" → GraphEdge
 *   - HTTP 2xx with any other JSON object → GraphNode wrapping the
 *     raw object
 *   - HTTP 2xx with a non-JSON body or a non-object JSON body →
 *     FacebookApiException (Meta returning non-JSON on a successful
 *     call is a wire bug worth surfacing loudly)
 *   - HTTP non-2xx with a parseable Meta error envelope →
 *     GraphErrorException carrying the decoded GraphError
 *   - HTTP non-2xx with no parseable envelope → FacebookApiException
 *     with the status line as its message
 *
 * The decoder never returns null on error; it always throws. Callers
 * can therefore write straight-line success paths and catch at whatever
 * layer suits them.
 */
final class GraphResponseDecoder
{
    /**
     * Decode a Graph API response. Throws GraphErrorException on a
     * Meta error envelope, FacebookApiException on other failures.
     */
    public function decode(ResponseInterface $response): GraphNode|GraphEdge
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $json = json_decode($body);

        if ($status >= 200 && $status < 300) {
            if (!is_object($json)) {
                throw new FacebookApiException(
                    sprintf('Graph API returned %d with non-object body', $status),
                    $status,
                );
            }
            if (isset($json->data) && is_array($json->data)) {
                return new GraphEdge(
                    data: $json->data,
                    paging: isset($json->paging) && is_object($json->paging) ? $json->paging : null,
                );
            }
            return new GraphNode(raw: $json);
        }

        // Non-2xx: try to decode Meta's error envelope.
        if (is_object($json) && isset($json->error) && is_object($json->error)) {
            throw new GraphErrorException(
                error: GraphError::fromApiResponse($json->error),
                httpStatus: $status,
            );
        }

        throw new FacebookApiException(
            sprintf('Graph API returned HTTP %d %s', $status, $response->getReasonPhrase()),
            $status,
        );
    }
}

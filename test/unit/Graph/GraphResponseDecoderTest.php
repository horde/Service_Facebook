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

namespace Horde\Service\Facebook\Test\Graph;

use Horde\Http\Response;
use Horde\Http\StreamFactory;
use Horde\Service\Facebook\FacebookApiException;
use Horde\Service\Facebook\Graph\GraphEdge;
use Horde\Service\Facebook\Graph\GraphErrorException;
use Horde\Service\Facebook\Graph\GraphNode;
use Horde\Service\Facebook\Graph\GraphResponseDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(GraphResponseDecoder::class)]
final class GraphResponseDecoderTest extends TestCase
{
    public function testDecodesNodeResponseFromObjectBody(): void
    {
        $response = $this->makeResponse(200, '{"id":"123","name":"Alice"}');
        $decoded = (new GraphResponseDecoder())->decode($response);
        $this->assertInstanceOf(GraphNode::class, $decoded);
        $this->assertSame('123', $decoded->raw->id);
        $this->assertSame('Alice', $decoded->raw->name);
    }

    public function testDecodesEdgeResponseWhenDataArrayPresent(): void
    {
        $body = '{"data":[{"id":"1"},{"id":"2"}],"paging":{"next":"https://example.org/next"}}';
        $decoded = (new GraphResponseDecoder())->decode($this->makeResponse(200, $body));
        $this->assertInstanceOf(GraphEdge::class, $decoded);
        $this->assertCount(2, $decoded->data);
        $this->assertSame('https://example.org/next', $decoded->nextUrl());
    }

    public function testEdgeWithoutPagingHasNullNextUrl(): void
    {
        $decoded = (new GraphResponseDecoder())->decode($this->makeResponse(200, '{"data":[]}'));
        $this->assertInstanceOf(GraphEdge::class, $decoded);
        $this->assertNull($decoded->nextUrl());
    }

    public function testTwoXxWithNonObjectBodyRaisesFacebookApiException(): void
    {
        $this->expectException(FacebookApiException::class);
        (new GraphResponseDecoder())->decode($this->makeResponse(200, '"just a string"'));
    }

    public function testMetaErrorEnvelopeRaisesGraphErrorException(): void
    {
        $body = '{"error":{"message":"Invalid token","type":"OAuthException","code":190,"error_subcode":463}}';
        try {
            (new GraphResponseDecoder())->decode($this->makeResponse(401, $body));
            $this->fail('expected GraphErrorException');
        } catch (GraphErrorException $e) {
            $this->assertSame(401, $e->httpStatus());
            $this->assertTrue($e->error()->isTokenExpired());
            $this->assertSame('Invalid token', $e->error()->message);
        }
    }

    public function testNonTwoXxWithoutEnvelopeRaisesBareApiException(): void
    {
        $this->expectException(FacebookApiException::class);
        (new GraphResponseDecoder())->decode($this->makeResponse(500, '<html>gateway</html>'));
    }

    private function makeResponse(int $status, string $body): ResponseInterface
    {
        return new Response($status, [], (new StreamFactory())->createStream($body));
    }
}

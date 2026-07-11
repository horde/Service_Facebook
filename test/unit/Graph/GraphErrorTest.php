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

use Horde\Service\Facebook\Graph\GraphError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphError::class)]
final class GraphErrorTest extends TestCase
{
    public function testFromApiResponseCarriesAllFields(): void
    {
        $json = json_decode('{"message":"m","type":"OAuthException","code":190,"error_subcode":463,"fbtrace_id":"abc"}');
        $error = GraphError::fromApiResponse($json);
        $this->assertSame('m', $error->message);
        $this->assertSame('OAuthException', $error->type);
        $this->assertSame(190, $error->code);
        $this->assertSame(463, $error->subcode);
        $this->assertSame('abc', $error->traceId);
    }

    public function testFromApiResponseTolerantOfMissingOptionalFields(): void
    {
        $json = json_decode('{"message":"m","code":190}');
        $error = GraphError::fromApiResponse($json);
        $this->assertSame('m', $error->message);
        $this->assertSame('Unknown', $error->type);
        $this->assertNull($error->subcode);
        $this->assertNull($error->traceId);
    }

    public function testIsAuthMatchesCode190(): void
    {
        $error = GraphError::fromApiResponse(json_decode('{"code":190}'));
        $this->assertTrue($error->isAuth());
        $notAuth = GraphError::fromApiResponse(json_decode('{"code":100}'));
        $this->assertFalse($notAuth->isAuth());
    }

    public function testIsTokenExpiredMatchesSubcodes463And467(): void
    {
        $expired463 = GraphError::fromApiResponse(json_decode('{"code":190,"error_subcode":463}'));
        $expired467 = GraphError::fromApiResponse(json_decode('{"code":190,"error_subcode":467}'));
        $justAuth = GraphError::fromApiResponse(json_decode('{"code":190,"error_subcode":460}'));

        $this->assertTrue($expired463->isTokenExpired());
        $this->assertTrue($expired467->isTokenExpired());
        $this->assertFalse($justAuth->isTokenExpired());
    }

    public function testIsRateLimitMatchesDocumentedCodes(): void
    {
        foreach ([4, 17, 32, 613] as $code) {
            $error = GraphError::fromApiResponse((object) ['code' => $code]);
            $this->assertTrue($error->isRateLimit(), "code {$code}");
        }
        $notRate = GraphError::fromApiResponse(json_decode('{"code":190}'));
        $this->assertFalse($notRate->isRateLimit());
    }

    public function testAppAndUserRateLimitAreDistinct(): void
    {
        $app = GraphError::fromApiResponse(json_decode('{"code":4}'));
        $user = GraphError::fromApiResponse(json_decode('{"code":17}'));
        $this->assertTrue($app->isAppRateLimit());
        $this->assertFalse($app->isUserRateLimit());
        $this->assertFalse($user->isAppRateLimit());
        $this->assertTrue($user->isUserRateLimit());
    }

    public function testIsPermissionMissingMatchesCode200(): void
    {
        $error = GraphError::fromApiResponse(json_decode('{"code":200}'));
        $this->assertTrue($error->isPermissionMissing());
    }

    public function testIsTransientMatchesCodes1And2(): void
    {
        $one = GraphError::fromApiResponse(json_decode('{"code":1}'));
        $two = GraphError::fromApiResponse(json_decode('{"code":2}'));
        $other = GraphError::fromApiResponse(json_decode('{"code":190}'));
        $this->assertTrue($one->isTransient());
        $this->assertTrue($two->isTransient());
        $this->assertFalse($other->isTransient());
    }
}

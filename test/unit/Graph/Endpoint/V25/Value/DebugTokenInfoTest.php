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

namespace Horde\Service\Facebook\Test\Graph\Endpoint\V25\Value;

use DateTimeImmutable;
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\DebugTokenInfo;
use Horde\Service\Facebook\Graph\Value\DebugTokenInfo as DebugTokenInfoInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DebugTokenInfo::class)]
final class DebugTokenInfoTest extends TestCase
{
    public function testHydratesFullShapeAndImplementsSharedInterface(): void
    {
        $info = DebugTokenInfo::fromApiResponse((object) [
            'app_id' => '111',
            'user_id' => '222',
            'is_valid' => true,
            'expires_at' => 1_800_000_000,
            'issued_at' => 1_700_000_000,
            'application' => 'MyApp',
            'scopes' => ['public_profile', 'email'],
        ]);

        $this->assertInstanceOf(DebugTokenInfoInterface::class, $info);
        $this->assertSame('111', $info->appId());
        $this->assertSame('222', $info->userId());
        $this->assertTrue($info->isValid());
        $this->assertInstanceOf(DateTimeImmutable::class, $info->expiresAt());
        $this->assertSame(1_800_000_000, $info->expiresAt()->getTimestamp());
        $this->assertSame(1_700_000_000, $info->issuedAt?->getTimestamp());
        $this->assertSame('MyApp', $info->application);
        $this->assertSame(['public_profile', 'email'], $info->scopes);
    }

    public function testNonExpiringTokenGetsNullExpiresAt(): void
    {
        $info = DebugTokenInfo::fromApiResponse((object) [
            'app_id' => '111',
            'is_valid' => true,
            'expires_at' => 0,
        ]);
        $this->assertNull($info->expiresAt());
    }

    public function testAppTokenHasNullUserId(): void
    {
        $info = DebugTokenInfo::fromApiResponse((object) [
            'app_id' => '111',
            'is_valid' => true,
        ]);
        $this->assertNull($info->userId());
    }
}

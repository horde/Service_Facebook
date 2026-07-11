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

use Horde\Service\Facebook\Graph\GraphApiVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphApiVersion::class)]
final class GraphApiVersionTest extends TestCase
{
    public function testUrlSegmentReturnsBackingValue(): void
    {
        $this->assertSame('v25.0', GraphApiVersion::V25_0->urlSegment());
        $this->assertSame('v24.0', GraphApiVersion::V24_0->urlSegment());
        $this->assertSame('v23.0', GraphApiVersion::V23_0->urlSegment());
        $this->assertSame('v22.0', GraphApiVersion::V22_0->urlSegment());
    }

    public function testDefaultReturnsHighestSupportedVersion(): void
    {
        // The default() choice is a policy call; when it changes, this
        // test moves with it. Pinning the current expectation keeps
        // accidental changes visible.
        $this->assertSame(GraphApiVersion::V25_0, GraphApiVersion::default());
    }

    public function testFromStringRoundTripsEveryCase(): void
    {
        foreach (GraphApiVersion::cases() as $case) {
            $this->assertSame($case, GraphApiVersion::from($case->value));
        }
    }

    public function testTryFromReturnsNullForUnknownVersion(): void
    {
        $this->assertNull(GraphApiVersion::tryFrom('v99.0'));
        $this->assertNull(GraphApiVersion::tryFrom(''));
    }
}

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

namespace Horde\Service\Facebook\Test\Graph\Pagination;

use Horde\Service\Facebook\Graph\Pagination\Cursor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cursor::class)]
final class CursorTest extends TestCase
{
    public function testEmptyCursorEmitsNoQueryParams(): void
    {
        $this->assertSame([], (new Cursor())->toQueryParams());
    }

    public function testOnlyPopulatedFieldsAppearInQueryParams(): void
    {
        $params = (new Cursor(after: 'a-cursor'))->toQueryParams();
        $this->assertSame(['after' => 'a-cursor'], $params);
    }

    public function testAllFieldsRoundTripped(): void
    {
        $c = new Cursor(before: 'b', after: 'a', limit: 50);
        $this->assertSame(['before' => 'b', 'after' => 'a', 'limit' => 50], $c->toQueryParams());
    }

    public function testZeroLimitIsPreservedAsExplicitChoice(): void
    {
        // limit=0 is a legitimate (if degenerate) request; only null
        // should mean "omit".
        $c = new Cursor(limit: 0);
        $this->assertSame(['limit' => 0], $c->toQueryParams());
    }
}

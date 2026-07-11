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

use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Permission;
use Horde\Service\Facebook\Graph\Value\Permission as PermissionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Permission::class)]
final class PermissionTest extends TestCase
{
    public function testHydratesAndImplementsSharedInterface(): void
    {
        $permission = Permission::fromApiResponse((object) [
            'permission' => 'public_profile',
            'status' => 'granted',
        ]);

        $this->assertInstanceOf(PermissionInterface::class, $permission);
        $this->assertSame('public_profile', $permission->name());
        $this->assertSame('granted', $permission->status());
        $this->assertTrue($permission->isGranted());
    }

    public function testUnknownStatusReadsAsNotGranted(): void
    {
        $permission = Permission::fromApiResponse((object) [
            'permission' => 'user_events',
            'status' => 'expired',
        ]);
        $this->assertFalse($permission->isGranted());
    }

    public function testMissingStatusDefaultsToDeclined(): void
    {
        $permission = Permission::fromApiResponse((object) ['permission' => 'email']);
        $this->assertSame('declined', $permission->status());
        $this->assertFalse($permission->isGranted());
    }
}

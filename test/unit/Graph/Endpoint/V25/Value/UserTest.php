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

use Horde\Service\Facebook\Graph\Endpoint\V25\Value\User;
use Horde\Service\Facebook\Graph\Value\User as UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    public function testHydratesCoreFieldsAndImplementsSharedInterface(): void
    {
        $user = User::fromApiResponse((object) ['id' => '42', 'name' => 'Ada Lovelace']);
        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertSame('42', $user->id());
        $this->assertSame('Ada Lovelace', $user->name());
    }

    public function testHydratesV25SpecificFieldsWhenPresent(): void
    {
        $user = User::fromApiResponse((object) [
            'id' => '42',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.org',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'link' => 'https://facebook.com/ada',
        ]);
        $this->assertSame('ada@example.org', $user->email);
        $this->assertSame('Ada', $user->firstName);
        $this->assertSame('Lovelace', $user->lastName);
        $this->assertSame('https://facebook.com/ada', $user->link);
    }

    public function testOptionalFieldsAreNullWhenAbsent(): void
    {
        $user = User::fromApiResponse((object) ['id' => '42', 'name' => 'Ada']);
        $this->assertNull($user->email);
        $this->assertNull($user->firstName);
        $this->assertNull($user->link);
    }
}

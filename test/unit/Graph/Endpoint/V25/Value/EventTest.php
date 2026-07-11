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
use Horde\Service\Facebook\Graph\Endpoint\V25\Value\Event;
use Horde\Service\Facebook\Graph\Value\Event as EventInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Event::class)]
final class EventTest extends TestCase
{
    public function testHydratesCoreFieldsAndImplementsSharedInterface(): void
    {
        $event = Event::fromApiResponse((object) [
            'id' => '999',
            'name' => 'PHP Meetup',
            'start_time' => '2026-08-15T18:00:00+0000',
        ]);

        $this->assertInstanceOf(EventInterface::class, $event);
        $this->assertSame('999', $event->id());
        $this->assertSame('PHP Meetup', $event->name());
        $this->assertInstanceOf(DateTimeImmutable::class, $event->startTime());
        $this->assertSame('2026-08-15T18:00:00+00:00', $event->startTime()->format(DateTimeImmutable::ATOM));
    }

    public function testHydratesFullShape(): void
    {
        $event = Event::fromApiResponse((object) [
            'id' => '999',
            'name' => 'PHP Meetup',
            'start_time' => '2026-08-15T18:00:00+0000',
            'end_time' => '2026-08-15T21:00:00+0000',
            'description' => 'Come talk about PHP',
            'rsvp_status' => 'attending',
            'place' => (object) ['name' => 'Community Center'],
            'owner' => (object) ['id' => '1', 'name' => 'Org'],
            'cover' => (object) ['source' => 'https://example.org/cover.jpg'],
        ]);

        $this->assertSame('Come talk about PHP', $event->description);
        $this->assertSame('attending', $event->rsvpStatus);
        $this->assertSame('Community Center', $event->placeName);
        $this->assertSame('1', $event->ownerId);
        $this->assertSame('Org', $event->ownerName);
        $this->assertSame('https://example.org/cover.jpg', $event->coverPhotoUrl);
        $this->assertNotNull($event->endTime);
        $this->assertSame('2026-08-15T21:00:00+00:00', $event->endTime->format(DateTimeImmutable::ATOM));
    }

    public function testMalformedStartTimeDegradesToEpoch(): void
    {
        // Malformed timestamps degrade to null in parseTime, which the
        // constructor replaces with @0. That is a defensive choice so
        // hydration never throws mid-loop; the calling test asserts
        // the boundary condition explicitly.
        $event = Event::fromApiResponse((object) [
            'id' => '999',
            'name' => 'Bad',
            'start_time' => 'not a timestamp',
        ]);
        $this->assertSame(0, $event->startTime()->getTimestamp());
    }

    public function testEndTimeAbsentWhenMetaOmitsIt(): void
    {
        $event = Event::fromApiResponse((object) [
            'id' => '999',
            'name' => 'Ongoing',
            'start_time' => '2026-08-15T18:00:00+0000',
        ]);
        $this->assertNull($event->endTime);
    }
}

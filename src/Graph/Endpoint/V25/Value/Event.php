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

namespace Horde\Service\Facebook\Graph\Endpoint\V25\Value;

use DateTimeImmutable;
use Horde\Service\Facebook\Graph\Value\Event as EventInterface;
use Exception;

/**
 * Event value object for Graph API v25 /me/events row shape.
 *
 * Implements Horde\Service\Facebook\Graph\Value\Event for the core
 * fields present on every supported version (id, name, startTime) and
 * adds v25-specific fields — endTime, description, place, RSVP status,
 * cover photo URL, owner — as public readonly properties.
 *
 * Meta returns start_time and end_time as ISO 8601 timestamps. This
 * class parses both to DateTimeImmutable at hydration time so callers
 * never see raw strings. A malformed timestamp is treated as absent
 * (null) rather than throwing; a downstream caller reading the raw
 * response via the ?fields= parameter can still detect the underlying
 * bug via the endpoint layer's raw-access surface.
 */
final class Event implements EventInterface
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly DateTimeImmutable $startTime,
        public readonly ?DateTimeImmutable $endTime = null,
        public readonly ?string $description = null,
        public readonly ?string $rsvpStatus = null,
        public readonly ?string $coverPhotoUrl = null,
        public readonly ?string $placeName = null,
        public readonly ?string $ownerId = null,
        public readonly ?string $ownerName = null,
    ) {}

    public static function fromApiResponse(object $data): self
    {
        $startTime = self::parseTime($data->start_time ?? null) ?? new DateTimeImmutable('@0');
        $endTime = self::parseTime($data->end_time ?? null);

        $placeName = null;
        if (isset($data->place) && is_object($data->place) && isset($data->place->name)) {
            $placeName = (string) $data->place->name;
        }

        $ownerId = null;
        $ownerName = null;
        if (isset($data->owner) && is_object($data->owner)) {
            $ownerId = isset($data->owner->id) ? (string) $data->owner->id : null;
            $ownerName = isset($data->owner->name) ? (string) $data->owner->name : null;
        }

        $coverPhotoUrl = null;
        if (isset($data->cover) && is_object($data->cover) && isset($data->cover->source)) {
            $coverPhotoUrl = (string) $data->cover->source;
        }

        return new self(
            id: (string) ($data->id ?? ''),
            name: (string) ($data->name ?? ''),
            startTime: $startTime,
            endTime: $endTime,
            description: isset($data->description) ? (string) $data->description : null,
            rsvpStatus: isset($data->rsvp_status) ? (string) $data->rsvp_status : null,
            coverPhotoUrl: $coverPhotoUrl,
            placeName: $placeName,
            ownerId: $ownerId,
            ownerName: $ownerName,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function startTime(): DateTimeImmutable
    {
        return $this->startTime;
    }

    private static function parseTime(mixed $raw): ?DateTimeImmutable
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($raw);
        } catch (Exception) {
            return null;
        }
    }
}

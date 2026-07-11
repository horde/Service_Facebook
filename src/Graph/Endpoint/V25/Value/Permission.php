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

use Horde\Service\Facebook\Graph\Value\Permission as PermissionInterface;

/**
 * Permission value object for Graph API v25 /me/permissions rows.
 *
 * Meta returns each row as {"permission":"public_profile","status":"granted"}.
 * Implementation is trivial because the shape is trivial and has been
 * unchanged since Graph v2.
 */
final class Permission implements PermissionInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
    ) {}

    public static function fromApiResponse(object $data): self
    {
        return new self(
            name: (string) ($data->permission ?? ''),
            status: (string) ($data->status ?? 'declined'),
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * Convenience: is this permission currently granted?
     * Meta emits "granted" for granted permissions and "declined" for
     * everything else; any other value is treated as declined so
     * unexpected string values do not accidentally read as granted.
     */
    public function isGranted(): bool
    {
        return $this->status === 'granted';
    }
}

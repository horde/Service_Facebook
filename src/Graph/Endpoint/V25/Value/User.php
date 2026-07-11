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

use Horde\Service\Facebook\Graph\Value\User as UserInterface;

/**
 * User value object for Graph API v25 /me responses.
 *
 * Implements the version-neutral Horde\Service\Facebook\Graph\Value\User
 * shared interface for the identifier fields (id, name) present on
 * every supported Graph version, and adds v25-specific optional fields
 * as public readonly properties.
 *
 * Field availability is scope-dependent. Meta's public_profile scope
 * grants id and name; email requires the email scope; picture requires
 * public_profile plus the /picture edge to be queried explicitly. The
 * optional fields on this class stay null when Meta does not return
 * them, whether because the scope is missing or because the caller
 * did not include the field in the ?fields= query parameter.
 */
final class User implements UserInterface
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $middleName = null,
        public readonly ?string $shortName = null,
        public readonly ?string $link = null,
    ) {}

    public static function fromApiResponse(object $data): self
    {
        return new self(
            id: (string) ($data->id ?? ''),
            name: (string) ($data->name ?? ''),
            email: isset($data->email) ? (string) $data->email : null,
            firstName: isset($data->first_name) ? (string) $data->first_name : null,
            lastName: isset($data->last_name) ? (string) $data->last_name : null,
            middleName: isset($data->middle_name) ? (string) $data->middle_name : null,
            shortName: isset($data->short_name) ? (string) $data->short_name : null,
            link: isset($data->link) ? (string) $data->link : null,
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
}

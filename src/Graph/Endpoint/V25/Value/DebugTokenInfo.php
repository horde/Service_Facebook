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
use Horde\Service\Facebook\Graph\Value\DebugTokenInfo as DebugTokenInfoInterface;

/**
 * DebugTokenInfo value object for Graph API v25 /debug_token responses.
 *
 * Meta wraps the payload in a `data` key at the transport layer; this
 * class hydrates from the inner object (i.e. $response->data), which
 * the endpoint layer extracts before calling fromApiResponse. Fields
 * present on every supported version go on the shared interface; v25
 * adds application, scopes, and issued_at as public readonly
 * properties.
 *
 * Meta returns `expires_at` as a Unix timestamp integer (0 for
 * non-expiring tokens); this class converts to DateTimeImmutable at
 * hydration or null when zero, so callers never do timestamp arithmetic
 * themselves.
 */
final class DebugTokenInfo implements DebugTokenInfoInterface
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly string $appId,
        public readonly ?string $userId,
        public readonly bool $isValid,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly ?DateTimeImmutable $issuedAt,
        public readonly ?string $application,
        public readonly array $scopes,
    ) {}

    public static function fromApiResponse(object $data): self
    {
        $expiresAt = null;
        if (isset($data->expires_at) && (int) $data->expires_at > 0) {
            $expiresAt = (new DateTimeImmutable())->setTimestamp((int) $data->expires_at);
        }

        $issuedAt = null;
        if (isset($data->issued_at) && (int) $data->issued_at > 0) {
            $issuedAt = (new DateTimeImmutable())->setTimestamp((int) $data->issued_at);
        }

        $scopes = [];
        if (isset($data->scopes) && is_array($data->scopes)) {
            foreach ($data->scopes as $scope) {
                if (is_string($scope)) {
                    $scopes[] = $scope;
                }
            }
        }

        return new self(
            appId: (string) ($data->app_id ?? ''),
            userId: isset($data->user_id) ? (string) $data->user_id : null,
            isValid: (bool) ($data->is_valid ?? false),
            expiresAt: $expiresAt,
            issuedAt: $issuedAt,
            application: isset($data->application) ? (string) $data->application : null,
            scopes: $scopes,
        );
    }

    public function appId(): string
    {
        return $this->appId;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
}

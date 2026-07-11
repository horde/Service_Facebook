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
use Horde\Service\Facebook\Graph\ProfileUrls;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileUrls::class)]
final class ProfileUrlsTest extends TestCase
{
    public function testProfileLinkForNumericUid(): void
    {
        $this->assertSame(
            'https://www.facebook.com/12345',
            ProfileUrls::profileLink('12345'),
        );
    }

    public function testProfileLinkForVanityName(): void
    {
        $this->assertSame(
            'https://www.facebook.com/zuck',
            ProfileUrls::profileLink('zuck'),
        );
    }

    public function testProfileLinkEncodesExoticCharacters(): void
    {
        $this->assertSame(
            'https://www.facebook.com/some%2Fname',
            ProfileUrls::profileLink('some/name'),
        );
    }

    public function testThumbnailUrlDefaultsToCurrentVersion(): void
    {
        $expected = 'https://graph.facebook.com/'
            . GraphApiVersion::default()->urlSegment() . '/12345/picture';
        $this->assertSame($expected, ProfileUrls::thumbnailUrl('12345'));
    }

    public function testThumbnailUrlWithExplicitVersion(): void
    {
        $this->assertSame(
            'https://graph.facebook.com/v22.0/12345/picture',
            ProfileUrls::thumbnailUrl('12345', GraphApiVersion::V22_0),
        );
    }

    public function testThumbnailUrlAppendsTypeOption(): void
    {
        $url = ProfileUrls::thumbnailUrl('12345', GraphApiVersion::V25_0, ['type' => 'large']);
        $this->assertStringContainsString('/v25.0/12345/picture', $url);
        $this->assertStringContainsString('?type=large', $url);
    }

    public function testThumbnailUrlAppendsPixelDimensions(): void
    {
        $url = ProfileUrls::thumbnailUrl(
            '12345',
            GraphApiVersion::V25_0,
            ['width' => 200, 'height' => 200],
        );
        $this->assertStringContainsString('width=200', $url);
        $this->assertStringContainsString('height=200', $url);
    }

    public function testThumbnailUrlEncodesUid(): void
    {
        $url = ProfileUrls::thumbnailUrl('a/b');
        $this->assertStringContainsString('/a%2Fb/picture', $url);
    }

    public function testThumbnailUrlOmitsQueryWhenNoOptions(): void
    {
        $this->assertStringNotContainsString('?', ProfileUrls::thumbnailUrl('12345'));
    }
}

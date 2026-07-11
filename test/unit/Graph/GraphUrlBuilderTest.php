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
use Horde\Service\Facebook\Graph\GraphUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphUrlBuilder::class)]
final class GraphUrlBuilderTest extends TestCase
{
    public function testPrependsVersionSegmentToLeadingSlashPath(): void
    {
        $builder = new GraphUrlBuilder('https://graph.facebook.com', GraphApiVersion::V25_0);
        $this->assertSame('https://graph.facebook.com/v25.0/me', $builder->build('/me'));
    }

    public function testAcceptsPathWithoutLeadingSlash(): void
    {
        $builder = new GraphUrlBuilder('https://graph.facebook.com', GraphApiVersion::V25_0);
        $this->assertSame('https://graph.facebook.com/v25.0/me', $builder->build('me'));
    }

    public function testTrimsTrailingSlashFromBaseUrl(): void
    {
        $builder = new GraphUrlBuilder('https://graph.facebook.com/', GraphApiVersion::V25_0);
        $this->assertSame('https://graph.facebook.com/v25.0/me/events', $builder->build('/me/events'));
    }

    public function testAppendsQueryParametersWithRfc3986Encoding(): void
    {
        $builder = new GraphUrlBuilder('https://graph.facebook.com', GraphApiVersion::V25_0);
        $url = $builder->build('/me', ['fields' => 'id,name email']);
        $this->assertStringContainsString('?fields=id%2Cname%20email', $url);
    }

    public function testOmitsQueryStringWhenParamsEmpty(): void
    {
        $builder = new GraphUrlBuilder('https://graph.facebook.com', GraphApiVersion::V25_0);
        $this->assertStringNotContainsString('?', $builder->build('/me', []));
    }

    public function testUsesActiveVersionSegment(): void
    {
        $builder = new GraphUrlBuilder('https://graph.facebook.com', GraphApiVersion::V22_0);
        $this->assertSame('https://graph.facebook.com/v22.0/me', $builder->build('/me'));
        $this->assertSame(GraphApiVersion::V22_0, $builder->version());
    }
}

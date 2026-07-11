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

namespace Horde\Service\Facebook\Test\Graph\Endpoint;

use Horde\Http\Client\Mock as MockHttpClient;
use Horde\Http\RequestFactory;
use Horde\Service\Facebook\FacebookApiConfig;
use Horde\Service\Facebook\Graph\Endpoint\EndpointResolver;
use Horde\Service\Facebook\Graph\Endpoint\V25\DebugTokenEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\EventsEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\MeEndpoint;
use Horde\Service\Facebook\Graph\Endpoint\V25\PermissionsEndpoint;
use Horde\Service\Facebook\Graph\GraphApiVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EndpointResolver::class)]
final class EndpointResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{GraphApiVersion}>
     */
    public static function everyVersion(): iterable
    {
        foreach (GraphApiVersion::cases() as $case) {
            yield $case->value => [$case];
        }
    }

    #[DataProvider('everyVersion')]
    public function testMeResolvesToConcreteEndpointForEveryVersion(GraphApiVersion $version): void
    {
        $this->assertInstanceOf(MeEndpoint::class, $this->resolver($version)->me($version));
    }

    #[DataProvider('everyVersion')]
    public function testEventsResolvesForEveryVersion(GraphApiVersion $version): void
    {
        $this->assertInstanceOf(EventsEndpoint::class, $this->resolver($version)->events($version));
    }

    #[DataProvider('everyVersion')]
    public function testPermissionsResolvesForEveryVersion(GraphApiVersion $version): void
    {
        $this->assertInstanceOf(PermissionsEndpoint::class, $this->resolver($version)->permissions($version));
    }

    #[DataProvider('everyVersion')]
    public function testDebugTokenResolvesForEveryVersion(GraphApiVersion $version): void
    {
        $this->assertInstanceOf(DebugTokenEndpoint::class, $this->resolver($version)->debugToken($version));
    }

    private function resolver(GraphApiVersion $version): EndpointResolver
    {
        return new EndpointResolver(
            new MockHttpClient(),
            new RequestFactory(),
            new FacebookApiConfig(version: $version),
        );
    }
}

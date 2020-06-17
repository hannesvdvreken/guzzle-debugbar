<?php

namespace GuzzleHttp\Profiling\Debugbar\Unit\Support\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Profiling\Debugbar\Support\Laravel\ServiceProvider;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testAttributes()
    {
        // Arrange
        $app = $this->createMock(Container::class);
        $serviceProvider = new ServiceProvider($app);

        // Act
        $deferred = $serviceProvider->isDeferred();
        $provides = $serviceProvider->provides();

        // Assert
        $this->assertTrue($deferred);
        $this->assertContains(Client::class, $provides);
        $this->assertContains(ClientInterface::class, $provides);
        $this->assertContains(HandlerStack::class, $provides);
    }
}

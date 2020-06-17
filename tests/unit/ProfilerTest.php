<?php

namespace GuzzleHttp\Profiling\Debugbar\Unit;

use DebugBar\DataCollector\TimeDataCollector;
use GuzzleHttp\Profiling\Debugbar\Profiler;
use GuzzleHttp\Profiling\Debugbar\Unit\Stubs\Profiler as ProfilerStub;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ProfilerTest extends TestCase
{
    public function testTimelineIsCalled()
    {
        // Arrange
        $timeline = $this->getMockBuilder(TimeDataCollector::class)->getMock();
        $profiler = new Profiler($timeline);
        $request = new Request('GET', 'http://httpbin.org/status/418');
        $response = new Response(418);

        $params = [
            'host' => 'httpbin.org',
            'method' => 'GET',
            'url' => 'http://httpbin.org/status/418',
            'status_code' => '418',
            'phrase' => 'I\'m a teapot',
        ];

        // Set expectations
        $timeline
            ->expects($this->once())
            ->method('addMeasure')
            ->with(
                'GET http://httpbin.org/status/418 returned 418 I\'m a teapot', // This is not under test.
                $start = microtime(true),
                $end = microtime(true),
                $params,
                'guzzle'
            );

        // Act
        $profiler->add($start, $end, $request, $response);

        // Assert
    }

    public function testAdjustedContextGivesMoreData()
    {
        // Arrange
        $timeline = $this->getMockBuilder(TimeDataCollector::class)->getMock();
        $profiler = new ProfilerStub($timeline);
        $request = new Request('GET', 'http://httpbin.org/status/418');
        $response = new Response(418);

        $params = [
            'host' => 'httpbin.org',
            'method' => 'GET',
            'url' => 'http://httpbin.org/status/418',
            'status_code' => '418',
            'phrase' => 'I\'m a teapot',
            'resource' => 'http://httpbin.org/status/418',
            'request_version' => '1.1',
            'response_version' => '1.1',
            'hostname' => gethostname(),
        ];

        // Set expectations
        $timeline
            ->expects($this->once())
            ->method('addMeasure')
            ->with(
                'GET http://httpbin.org/status/418 returned 418 I\'m a teapot', // This is not under test.
                $start = microtime(true),
                $end = microtime(true),
                $params,
                'guzzle'
            );

        // Act
        $profiler->add($start, $end, $request, $response);

        // Assert
    }
}

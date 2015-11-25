<?php
namespace GuzzleHttp\Profiling\Debugbar\Unit;

use DebugBar\DataCollector\ExceptionsCollector;
use Exception;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Profiling\Debugbar\ExceptionMiddleware as Middleware;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExceptionMiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testMiddlewareReturnsCallable()
    {
        // Arrange
        $collector = $this->getMock(ExceptionsCollector::class);
        $middleware = new Middleware($collector);

        $called = false;

        $handler = function() use (&$called) {
            if (!$called) {
                $called = true;
            }
        };

        // Act
        $function = $middleware($handler);

        // Assert
        $this->assertInternalType('callable', $function);
        $this->assertFalse($called);
    }

    public function testNextMiddlewareIsCalled()
    {
        // Arrange
        $collector = $this->getMock(ExceptionsCollector::class);
        $middleware = new Middleware($collector);
        $options = [
            'random' => 'data',
        ];

        $promise = $this->getMockBuilder(Promise::class)->getMock();
        $promise
            ->expects($this->once())
            ->method('then')
            ->with(
                $this->callback(function ($callback) {
                    return true;
                }),
                $this->callback(function ($callback) {
                    return true;
                })
            )->willReturn($promise);

        // Next handler that will be passed when creating the inner middleware function.
        $called = false;

        $handler = function(RequestInterface $request, array $passedOptions) use (&$called, $promise, $options) {
            if (!$called) {
                $called = true;
            }

            $this->assertEquals($options, $passedOptions);

            return $promise;
        };

        // Get inner callable.
        $middleware = $middleware($handler);

        $request = new Request('GET', 'https://httpbin.org/status/200');

        // Act
        $returnedPromise = $middleware($request, $options);

        // Assert
        $this->assertTrue($called);
        $this->assertEquals($promise, $returnedPromise);
    }

    function testFulfilledCallback() {
        // Arrange
        $collector = $this->getMockBuilder(ExceptionsCollector::class)->getMock();
        $exception = $this->getMock(TransferException::class);
        $response = $this->getMock(ResponseInterface::class);
        $middleware = new Middleware($collector);

        $collector
            ->expects($this->once())
            ->method('addException')
            ->with($exception);

        $promise = $this->getMockBuilder(Promise::class)->getMock();
        $promise
            ->expects($this->once())
            ->method('then')
            ->with(
                $this->callback(function ($callback) use ($response) {
                    // Act
                    $returnedResponse = $callback($response);

                    // Assert
                    $this->assertEquals($response, $returnedResponse);

                    return true;
                }),
                $this->callback(function ($callback) use ($exception) {
                    // Act
                    try {
                        $callback($exception);
                    } catch (Exception $thrownException) {
                        $this->assertEquals($exception, $thrownException);
                    }

                    // Assert


                    return true;
                })
            )->willReturn($promise);

        // Next handler that will be passed when creating the inner middleware function.
        $called = false;

        $handler = function(RequestInterface $request, array $options) use (&$called, $promise) {
            if (!$called) {
                $called = true;
            }

            return $promise;
        };

        // Get inner callable.
        $middleware = $middleware($handler);

        // Act
        $returnedPromise = $middleware(new Request('GET', 'https://httpbin.org/status/200'), []);

        // Assert
        $this->assertEquals($promise, $returnedPromise);
    }
}

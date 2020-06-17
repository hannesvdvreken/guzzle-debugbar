<?php

namespace GuzzleHttp\Profiling\Debugbar;

use DebugBar\DataCollector\ExceptionsCollector;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExceptionMiddleware
{
    /**
     * @var \DebugBar\DataCollector\ExceptionsCollector
     */
    private $collector;

    /**
     * ExceptionMiddleware constructor.
     *
     * @param \DebugBar\DataCollector\ExceptionsCollector $collector
     */
    public function __construct(ExceptionsCollector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        return function(RequestInterface $request, array $options) use ($handler): PromiseInterface {
            return $handler($request, $options)
                ->then(function(ResponseInterface $response) {
                    return $response;
                }, function(ClientExceptionInterface $exception) {
                    $this->collector->addException($exception);

                    throw $exception;
                });
        };
    }
}

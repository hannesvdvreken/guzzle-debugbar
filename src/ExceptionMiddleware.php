<?php
namespace GuzzleHttp\Profiling\Debugbar;

use DebugBar\DataCollector\ExceptionsCollector;
use GuzzleHttp\Exception\GuzzleException;
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler($request, $options)
                ->then(function (ResponseInterface $response) {
                    return $response;
                }, function (GuzzleException $exception) {
                    $this->collector->addException($exception);

                    throw $exception;
                });
        };
    }
}

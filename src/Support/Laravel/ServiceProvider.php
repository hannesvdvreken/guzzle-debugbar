<?php

namespace GuzzleHttp\Profiling\Debugbar\Support\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\Profiling\Debugbar\ExceptionMiddleware;
use GuzzleHttp\Profiling\Debugbar\Profiler;
use GuzzleHttp\Profiling\Middleware;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Http\Client\ClientInterface as PsrClientInterface;

class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * @return array
     */
    public function provides(): array
    {
        return [
            Client::class,
            ClientInterface::class,
            PsrClientInterface::class,
            HandlerStack::class,
        ];
    }

    /**
     * Register method.
     */
    public function register(): void
    {
        // Configuring all guzzle clients.
        $this->app->bind(PsrClientInterface::class, function(): PsrClientInterface {
            // Guzzle client
            return new Client(['handler' => $this->app->make(HandlerStack::class)]);
        });

        $this->app->alias(PsrClientInterface::class, Client::class);
        $this->app->alias(PsrClientInterface::class, ClientInterface::class);

        // Bind if needed.
        $this->app->bind(HandlerStack::class, function(): HandlerStack {
            return HandlerStack::create();
        });

        // If resolved, by this SP or another, add some layers.
        $this->app->resolving(HandlerStack::class, function(HandlerStack $stack): void {
            // We cannot log with debugbar from the CLI
            if ($this->app->runningInConsole()) {
                return;
            }

            /** @var \DebugBar\DebugBar $debugBar */
            $debugBar = $this->app->make('debugbar');

            $stack->push(new Middleware(new Profiler($timeline = $debugBar->getCollector('time'))));
            $stack->unshift(new ExceptionMiddleware($debugBar->getCollector('exceptions')));

            /** @var \GuzzleHttp\MessageFormatter $formatter */
            $formatter = $this->app->make(MessageFormatter::class);
            $stack->unshift(GuzzleMiddleware::log($debugBar->getCollector('messages'), $formatter));

            // Also log to the default PSR logger.
            if ($this->app->bound(LoggerInterface::class)) {
                $logger = $this->app->make(LoggerInterface::class);

                // Don't log to the same logger twice.
                if ($logger === $debugBar->getCollector('messages')) {
                    return;
                }

                // Push the middleware on the stack.
                $stack->unshift(GuzzleMiddleware::log($logger, $formatter));
            }
        });
    }
}

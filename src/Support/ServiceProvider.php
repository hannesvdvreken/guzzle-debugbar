<?php
namespace GuzzleHttp\Subscriber\Log\Support;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DebugBar;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Log\LogSubscriber;
use GuzzleHttp\Subscriber\Log\DebugbarSubscriber;
use Psr\Log\LoggerInterface;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use DebugBar\DataCollector\TimeDataCollector;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register method
     */
    public function register()
    {
        $this->registerGuzzleSubscriber();
    }

    /**
     * Register a log subscriber on every Guzzle client.
     */
    private function registerGuzzleSubscriber()
    {
        // Register a log subscriber with every Guzzle client.
        $this->app->bind('GuzzleHttp\Client', function () {
            // Create new client.
            $client = new Client;

            /** @var DebugBar $debugBar */
            $debugBar = $this->app->make('debugbar');

            /** @var LoggerInterface $logger */
            $logger = $debugBar->getCollector('messages');

            /** @var TimeDataCollector $timeline */
            $timeline = $debugBar->getCollector('time');

            /** @var ExceptionsCollector $exceptions */
            $exceptions = $debugBar->getCollector('exceptions');

            // Attach event subscribers.
            $client->getEmitter()->attach(new LogSubscriber($logger));
            $client->getEmitter()->attach(new DebugbarSubscriber($timeline, $exceptions));

            // Return configured client.
            return $client;
        });
    }
}
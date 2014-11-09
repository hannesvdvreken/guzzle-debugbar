<?php
namespace GuzzleHttp\Subscriber\Log\Support;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Log\DebugbarSubscriber;
use GuzzleHttp\Subscriber\Log\LogSubscriber;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Log\LoggerInterface;

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
        $this->app->bind('GuzzleHttp\Client', function ($app, $params) {
            // Create new client.
            $client = new Client(array_shift($params) ?: []);

            /** @var DebugBar $debugBar */
            $debugBar = $this->app->make('debugbar');

            $this->registerLogSubsriber($client, $debugBar);
            $this->registerDebugbarSubscriber($client, $debugBar);

            // Return configured client.
            return $client;
        });
    }

    /**
     * @param Client $client
     * @param DebugBar $debugBar
     */
    private function registerLogSubsriber(Client $client, DebugBar $debugBar)
    {
        /** @var LoggerInterface $logger */
        $logger = $debugBar->getCollector('messages');

        // Attach event subscribers.
        $client->getEmitter()->attach(new LogSubscriber($logger));
    }

    /**
     * @param Client $client
     * @param DebugBar $debugBar
     */
    private function registerDebugbarSubscriber(Client $client, DebugBar $debugBar)
    {
        /** @var TimeDataCollector $timeline */
        $timeline = $debugBar->getCollector('time');

        /** @var ExceptionsCollector $exceptions */
        $exceptions = $debugBar->getCollector('exceptions');

        // Attach event subscribers.
        $client->getEmitter()->attach(new DebugbarSubscriber($timeline, $exceptions));
    }
}

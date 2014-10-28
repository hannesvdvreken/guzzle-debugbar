<?php
namespace GuzzleHttp\Subscriber\Log\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Log\DebugbarSubscriber;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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

            // Get debugbar.
            $debugBar = $this->app->make('debugbar');

            // Create new log subscriber for Guzzle.
            $subscriber = new DebugbarSubscriber($debugBar);

            // Attach event subscriber.
            $client->getEmitter()->attach($subscriber);

            // Return configured client.
            return $client;
        });
    }
}
<?php
namespace GuzzleHttp\Subscriber\Log;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class DebugbarSubscriber implements SubscriberInterface
{
    /**
     * @var DebugBar
     */
    private $debugBar;

    /**
     * @param DebugBar $debugBar
     */
    public function __construct(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return [
            'before'   => ['onBefore'],
            'complete' => ['onComplete'],
            'error'    => ['onError'],
        ];
    }

    /**
     * @param BeforeEvent $event
     */
    public function onBefore(BeforeEvent $event)
    {
        // Get request.
        $request = $event->getRequest();

        // Get unique identifier.
        $id = $this->createRequestID($request);

        // Start the event.
        $message = sprintf('Performing a %s request to %s.', $request->getMethod(), $request->getHeader('Host'));

        // Start time tracking.
        $this->getTimeline()->startMeasure("guzzle.request.$id", $message);
    }

    /**
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        // Get the guzzle request object.
        $request = $event->getRequest();

        // Stop measurement.
        $this->stopMeasure($request);
    }

    /**
     * @param ErrorEvent $event
     */
    public function onError(ErrorEvent $event)
    {
        if ($response = $event->getResponse()) {
            // Get request.
            $request = $event->getRequest();

            // Stop tracking for this request.
            $this->stopMeasure($request);

            // Build error message.
            $message = sprintf('%s %s returned %s', $request->getMethod(), $request->getUrl(), $response->getStatusCode());

            // Log the message about the request error.
            $this->getLogger()->error($message, compact('request', 'response'));
        } else {
            // Get the exception
            $exception = $event->getException();

            // And log the exception.
            $this->getExceptionsCollector()->addException($exception);
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    private function createRequestID(RequestInterface $request)
    {
        return spl_object_hash($request);
    }

    /**
     * @return TimeDataCollector
     */
    private function getTimeline()
    {
        return $this->debugBar->getCollector('time');
    }

    /**
     * @return ExceptionsCollector
     */
    private function getExceptionsCollector()
    {
        return $this->debugBar->getCollector('exceptions');
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->debugBar->getCollector('messages');
    }

    /**
     * @param $request
     */
    private function stopMeasure($request)
    {
        // Get a unique id.
        $id = $this->createRequestID($request);

        // Stop time tracking.
        $this->getTimeline()->stopMeasure("guzzle.request.$id");
    }
}
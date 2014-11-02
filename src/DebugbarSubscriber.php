<?php
namespace GuzzleHttp\Subscriber\Log;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\TimeDataCollector;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;

class DebugbarSubscriber implements SubscriberInterface
{
    /**
     * @var TimeDataCollector
     */
    private $timeline;

    /**
     * @var ExceptionsCollector
     */
    private $exceptions;

    /**
     * @param TimeDataCollector   $timeline
     * @param ExceptionsCollector $exceptions
     */
    public function __construct(TimeDataCollector $timeline, ExceptionsCollector $exceptions)
    {
        $this->timeline   = $timeline;
        $this->exceptions = $exceptions;
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
        // Make up an identifier.
        $identifier = $this->createTimelineID($event->getRequest());

        // Construct a timeline message
        $message = $this->createTimelineMessage($event->getRequest());

        // Start time tracking.
        $this->timeline->startMeasure($identifier, $message);
    }

    /**
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        // Stop measurement.
        $this->stopMeasure($event->getRequest());
    }

    /**
     * @param ErrorEvent $event
     */
    public function onError(ErrorEvent $event)
    {
        // Stop tracking the request.
        $this->stopMeasure($event->getRequest());

        // And log the exception.
        $this->exceptions->addException($event->getException());
    }

    /**
     * @param RequestInterface $request
     */
    private function stopMeasure(RequestInterface $request)
    {
        // Stop time tracking.
        $this->timeline->stopMeasure($this->createTimelineID($request));
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
     * @param RequestInterface $request
     *
     * @return string
     */
    private function createTimelineID(RequestInterface $request)
    {
        return 'guzzle.request.' . $this->createRequestID($request);
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    private function createTimelineMessage(RequestInterface $request)
    {
        return sprintf('Performing a %s request to %s.', $request->getMethod(), $request->getHeader('Host'));
    }
}

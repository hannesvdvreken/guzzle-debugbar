<?php
namespace GuzzleHttp\Subscriber\Log;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBarException;
use Exception;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

class DebugbarSubscriber implements SubscriberInterface
{
    /**
     * @var TimeDataCollector
     */
    protected $timeline;

    /**
     * @var ExceptionsCollector
     */
    protected $exceptions;

    /**
     * @var array
     */
    protected $startedMeasures = [];

    /**
     * @var array
     */
    protected $availableParameters = [
        'request', 'response', 'method', 'url', 'resource', 'request_version',
        'response_version', 'host', 'hostname', 'status_code', 'phrase', 'error',
    ];

    /**
     * @var array
     */
    protected $context = ['host', 'method', 'url', 'status_code', 'phrase', 'request', 'response'];

    /**
     * Public constructor.
     *
     * @param TimeDataCollector   $timeline
     * @param ExceptionsCollector $exceptions
     */
    public function __construct(TimeDataCollector $timeline, ExceptionsCollector $exceptions = null)
    {
        $this->timeline   = $timeline;
        $this->exceptions = $exceptions;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
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
     * Method to handle before events.
     *
     * @param BeforeEvent $event
     */
    public function onBefore(BeforeEvent $event)
    {
        // Start time tracking.
        $this->startMeasure($event->getRequest());
    }

    /**
     * Method to handle complete events.
     *
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        // Stop measurement.
        $this->stopMeasure($event->getRequest(), $event->getResponse());
    }

    /**
     * Method to handle error events.
     *
     * @param ErrorEvent $event
     */
    public function onError(ErrorEvent $event)
    {
        // Stop measurement and add exception information.
        $this->stopMeasure($event->getRequest(), $event->getResponse(), $event->getException());

        if ($this->exceptions) {
            $this->exceptions->addException($event->getException());
        }
    }

    /**
     * Start a measurement for a request.
     *
     * @param RequestInterface $request
     */
    protected function startMeasure(RequestInterface $request)
    {
        // Make up an identifier.
        $name = $this->createTimelineID($request);

        // Keep the start time in an array with the unique identifier.
        $this->startedMeasures[$name] = microtime(true);
    }

    /**
     * Stop the measurement.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param Exception         $error
     *
     * @throws DebugBarException
     */
    protected function stopMeasure(
        RequestInterface $request,
        ResponseInterface $response = null,
        Exception $error = null
    ) {
        $name = $this->createTimelineID($request);

        if (!isset($this->startedMeasures[$name])) {
            throw new DebugBarException("Failed stopping measure '$name' because it hasn't been started");
        }

        $start = $this->startedMeasures[$name];
        $end = microtime(true);
        $params = $this->getParameters($request, $response, $error);

        $this->timeline->addMeasure($this->createTimelineMessage($request, $response), $start, $end, $params, 'guzzle');

        unset($this->startedMeasures[$name]);
    }

    /**
     * Get context fields to add to the time-line entry.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param Exception         $error
     *
     * @return array
     */
    protected function getParameters(
        RequestInterface $request,
        ResponseInterface $response = null,
        Exception $error = null
    ) {
        $params = [];

        $keys = array_intersect($this->context, $this->availableParameters);

        foreach ($keys as $key) {
            switch ($key) {
                case 'request':
                    $result = $request;
                    break;
                case 'response':
                    $result = $response;
                    break;
                case 'method':
                    $result = $request->getMethod();
                    break;
                case 'url':
                    $result = $request->getUrl();
                    break;
                case 'resource':
                    $result = $request->getResource();
                    break;
                case 'request_version':
                    $result = $request->getProtocolVersion();
                    break;
                case 'response_version':
                    $result = $response ? $response->getProtocolVersion() : 'NULL';
                    break;
                case 'host':
                    $result = $request->getHost();
                    break;
                case 'hostname':
                    $result = gethostname();
                    break;
                case 'status_code':
                    $result = $response ? $response->getStatusCode() : 'NULL';
                    break;
                case 'phrase':
                    $result = $response ? $response->getReasonPhrase() : 'NULL';
                    break;
                case 'error':
                    $result = $error ? $error->getMessage() : 'NULL';
                    break;
            }

            $params[$key] = (string) $result ?: '';
        }

        return $params;
    }

    /**
     * Create a unique id for the request object.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    private function createRequestID(RequestInterface $request)
    {
        return spl_object_hash($request);
    }

    /**
     * Create a unique key for the measurements associative array.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    private function createTimelineID(RequestInterface $request)
    {
        return 'guzzle.request.' . $this->createRequestID($request);
    }

    /**
     * Build a string for displaying in the time-line containing useful info on the request.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return string
     */
    private function createTimelineMessage(RequestInterface $request, ResponseInterface $response = null)
    {
        $code = $response ? $response->getStatusCode() : 'NULL';
        return sprintf('%s %s (%s)', $request->getMethod(), $request->getUrl(), $code);
    }
}

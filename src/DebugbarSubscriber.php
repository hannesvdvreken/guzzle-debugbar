<?php
namespace GuzzleHttp\Subscriber\Log;

use DebugBar\DataCollector\TimeDataCollector;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use DebugBar\DebugBarException;

class DebugbarSubscriber implements SubscriberInterface
{
    /**
     * @var TimeDataCollector
     */
    protected $timeline;

    /**
     * @var array
     */
    protected $startedMeasures = array();

    /**
     * @param TimeDataCollector   $timeline
     */
    public function __construct(TimeDataCollector $timeline)
    {
        $this->timeline   = $timeline;
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
        // Start time tracking.
        $this->startMeasure($event->getRequest());
    }

    /**
     * @param CompleteEvent $event
     */
    public function onComplete(CompleteEvent $event)
    {
        // Stop measurement.
        $this->stopMeasure($event->getRequest(), $event->getResponse());
    }

    /**
     * @param ErrorEvent $event
     */
    public function onError(ErrorEvent $event)
    {
        // Stop measurement and add exception information.
        $this->stopMeasure($event->getRequest(), $event->getResponse(), $event->getException());
    }

    /**
     * Start a measure
     *
     * @param RequestInterface  $request
     */
    protected function startMeasure(RequestInterface $request)
    {
        // Make up an identifier.
        $name = $this->createTimelineID($request);

        $this->startedMeasures[$name] = microtime(true);
    }

    /**
     * Stop the measurement
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param \Exception $error
     *
     * @throws DebugBarException
     */
    protected function stopMeasure(RequestInterface $request, ResponseInterface $response = null, \Exception $error = null)
    {
        $end = microtime(true);
        $name = $this->createTimelineID($request);

        if (!isset($this->startedMeasures[$name])) {
            throw new DebugBarException("Failed stopping measure '$name' because it hasn't been started");
        }

        $this->timeline->addMeasure(
            $this->createTimelineMessage($request, $response),
            $this->startedMeasures[$name],
            $end,
            $this->getParameters($request, $response, $error),
            'guzzle'
        );

        unset($this->startedMeasures[$name]);
    }

    protected function getParameters(RequestInterface $request, ResponseInterface $response = null, \Exception $error = null)
    {
        $params = array();
        if($error){
            $params['error'] = $error->getMessage();
        }
        $keys = array(
            'method', 'url', 'host', 'code', 'phrase', 'request', 'response',
        );
        foreach($keys as $key){
            $result = '';
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
                case 'req_version':
                    $result = $request->getProtocolVersion();
                    break;
                case 'res_version':
                    $result = $response
                        ? $response->getProtocolVersion()
                        : 'NULL';
                    break;
                case 'host':
                    $result = $request->getHost();
                    break;
                case 'hostname':
                    $result = gethostname();
                    break;
                case 'code':
                    $result = $response
                        ? $response->getStatusCode()
                        : 'NULL';
                    break;
                case 'phrase':
                    $result = $response
                        ? $response->getReasonPhrase()
                        : 'NULL';
                    break;
            }
            $params[$key] = (string) $result;
        }

        return $params;
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

<?php

namespace GuzzleHttp\Profiling\Debugbar;

use DebugBar\DataCollector\TimeDataCollector;
use GuzzleHttp\Profiling\DescriptionMaker;
use GuzzleHttp\Profiling\Profiler as ProfilerContract;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Profiler implements ProfilerContract
{
    use DescriptionMaker;

    /**
     * @var \DebugBar\DataCollector\TimeDataCollector
     */
    protected $timeline;

    /**
     * @var array
     */
    protected $availableParameters = [
        'method', 'url', 'resource', 'request_version', 'response_version', 'host', 'hostname', 'status_code', 'phrase',
    ];

    /**
     * @var array
     */
    protected $context = ['host', 'method', 'url', 'status_code', 'phrase'];

    /**
     * Public constructor.
     *
     * @param \DebugBar\DataCollector\TimeDataCollector $timeline
     */
    public function __construct(TimeDataCollector $timeline)
    {
        $this->timeline = $timeline;
    }

    /**
     * @param float $start
     * @param float $end
     * @param \Psr\Http\Message\RequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function add(float $start, float $end, RequestInterface $request, ResponseInterface $response = null): void
    {
        $description = $this->describe($request, $response);
        $params = $this->getParameters($request, $response);

        $this->timeline->addMeasure($description, $start, $end, $params, 'guzzle');
    }

    /**
     * Get context fields to add to the time-line entry.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function getParameters(RequestInterface $request, ResponseInterface $response = null): array
    {
        $params = [];
        $result = '';

        $keys = array_intersect($this->context, $this->availableParameters);

        foreach ($keys as $key) {
            switch ($key) {
                case 'method':
                    $result = $request->getMethod();
                    break;
                case 'url':
                    $result = $request->getUri()->__toString();
                    break;
                case 'request_version':
                    $result = $request->getProtocolVersion();
                    break;
                case 'response_version':
                    $result = $response ? $response->getProtocolVersion() : 'NULL';
                    break;
                case 'host':
                    $result = $request->getUri()->getHost();
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
            }

            $params[$key] = (string) $result ?: '';
        }

        return $params;
    }
}

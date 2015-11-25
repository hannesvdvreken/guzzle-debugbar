<?php
namespace GuzzleHttp\Profiling\Debugbar\Unit\Stubs;

use GuzzleHttp\Profiling\Debugbar\Profiler as BaseProfiler;

class Profiler extends BaseProfiler
{
    protected $context = [
        'method', 'url', 'resource', 'request_version', 'response_version', 'host', 'hostname', 'status_code', 'phrase',
    ];
}

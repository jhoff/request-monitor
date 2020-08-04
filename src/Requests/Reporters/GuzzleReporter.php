<?php

namespace Jhoff\RequestMonitor\Requests\Reporters;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Jhoff\RequestMonitor\RequestMonitor;
use Jhoff\RequestMonitor\Requests\GuzzleRequest;

class GuzzleReporter {
    protected $monitor;

    public static function create(RequestMonitor $monitor)
    {
        return new static($monitor);
    }

    public function __construct(RequestMonitor $monitor)
    {
        $this->monitor = $monitor;
    }

    /**
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            list('scheme' => $scheme, 'host' => $host, 'path' => $path, 'query' => $query) = parse_url($request->getUri());

            parse_str($query, $params);

            $loggedRequest = new GuzzleRequest(
                "$scheme://$host/$path",
                $request->getMethod(),
                $params,
                $request->getHeaders()
            );

            return $handler($request, $options)
                ->then(function (ResponseInterface $response) use ($loggedRequest) {
                    $this->monitor->add($loggedRequest->end(
                        $response->getStatusCode(),
                        $response->getBody(),
                        $response->getHeaders()
                    ));

                    return $response;
                });
        };
    }
}

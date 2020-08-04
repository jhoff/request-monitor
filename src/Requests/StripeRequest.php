<?php

namespace Jhoff\RequestMonitor\Requests;

use Stripe\ApiRequestor;
use Jhoff\RequestMonitor\Request;
use Stripe\HttpClient\CurlClient;
use Jhoff\RequestMonitor\RequestMonitor;
use Jhoff\RequestMonitor\Requests\StripeRequest;

class StripeRequest extends Request
{
    public function getTypeAttribute(): string
    {
        return 'Stripe';
    }

    public static function enabled()
    {
        return class_exists(ApiRequestor::class) && class_exists(CurlClient::class);
    }

    public static function registerDefault(RequestMonitor $monitor)
    {
        ApiRequestor::setHttpClient(new class($monitor) extends CurlClient {
            protected $monitor;

            public function __construct(RequestMonitor $monitor, $defaultOptions = null, $randomGenerator = null)
            {
                $this->monitor = $monitor;

                parent::__construct($defaultOptions, $randomGenerator);
            }

            public function request($method, $absUrl, $headers, $params, $hasFile)
            {
                $request = new StripeRequest($absUrl, $method, $params, $headers);

                list($rbody, $rcode, $rheaders) = parent::request($method, $absUrl, $headers, $params, $hasFile);

                $this->monitor->add($request->end($rcode, $rbody, $rheaders->getIterator()->getArrayCopy()));

                return [$rbody, $rcode, $rheaders];
            }
        });
    }
}

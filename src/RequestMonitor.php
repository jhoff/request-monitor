<?php

namespace Jhoff\RequestMonitor;

use Exception;
use BadMethodCallException;
use Jhoff\RequestMonitor\Request;
use Illuminate\Contracts\Foundation\Application;

class RequestMonitor
{
    protected static $instance;

    protected $providers = [
        'guzzle' => Requests\GuzzleRequest::class,
        'stripe' => Requests\StripeRequest::class,
    ];

    protected $requests = [];

    public static function initialized()
    {
        return isset(static::$instance);
    }

    public static function initialize(array $providers = [])
    {
        return static::$instance = new static($providers);
    }

    public function __construct(array $providers = [])
    {
        $this->providers = array_merge($this->providers, $providers);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return static::$instance->$name(...$arguments);
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $method = '_' . $name)) {
            return $this->$method(...$arguments);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $name
        ));
    }

    protected function _registerWith(...$arguments)
    {
        foreach (array_filter($this->providers) as $provider => $class) {
            if (! class_exists($class) || ! is_subclass_of($class, Request::class)) {
                throw new Exception('Invalid RequestMonitor class provided for ' . $provider . ': ' . $class);
            }

            if ($class::enabled(...$arguments)) {
                $class::register($this, ...$arguments);
            }
        }
    }

    public function add(Request $request)
    {
        $this->requests[] = $request;

        return $this;
    }

    protected function _flush()
    {
        $requests = $this->requests;

        $this->requests = [];

        return empty($requests) ? null : $requests;
    }
}

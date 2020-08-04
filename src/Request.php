<?php

namespace Jhoff\RequestMonitor;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Str;
use Jhoff\RequestMonitor\RequestMonitor;

abstract class Request
{
    protected $attributes;

    protected static $customRegistration;

    public static function enabled()
    {
        return true;
    }

    public static function register(RequestMonitor $monitor, ...$arguments)
    {
        if (! is_null(static::$customRegistration)) {
            return call_user_func(static::$customRegistration, $monitor, ...$arguments);
        }

        if (method_exists(static::class, 'registerDefault')) {
            return static::registerDefault($monitor, ...$arguments);
        }

        throw new BadMethodCallException(sprintf(
            'No registerDefault or registerCustom methods found on %s.', static::class
        ));
    }

    public static function registerCustom(Closure $customRegistration)
    {
        static::$customRegistration = $customRegistration;
    }

    public function __construct(string $url, string $method, array $params = [], array $headers = [], int $start = null)
    {
        $this->attributes = [
            'url' => $url,
            'method' => strtoupper($method),
            'params' => $params,
            'headers' => $headers,
            'start' => $start ?? microtime(true),
        ];
    }

    public function end(int $responseCode = 200, string $responseBody = null, array $responseHeaders = [], int $end = null)
    {
        $this->attributes['responseCode'] = $responseCode;
        $this->attributes['responseBody'] = $responseBody;
        $this->attributes['responseHeaders'] = $responseHeaders;
        $this->attributes['end'] = $end ?? microtime(true);

        return $this;
    }

    abstract public function getTypeAttribute(): string;

    public function getDurationAttribute()
    {
        return ($this->end - $this->start) * 1000;
    }

    public function __get(string $name)
    {
        if (method_exists($this, $method = Str::camel("get_{$name}_attribute"))) {
            return $this->$method();
        }

        return $this->attributes[$name];
    }
}

<?php

namespace Jhoff\RequestMonitor\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\ClientInterface;
use Jhoff\RequestMonitor\Request;
use Jhoff\RequestMonitor\RequestMonitor;
use Illuminate\Contracts\Foundation\Application;
use Jhoff\RequestMonitor\Requests\Reporters\GuzzleReporter;

class GuzzleRequest extends Request
{
    public function getTypeAttribute(): string
    {
        return 'Guzzle';
    }

    public static function enabled(Application $app = null)
    {
        return $app !== null;
    }

    public static function registerDefault(RequestMonitor $monitor, Application $app)
    {
        $app->bind(ClientInterface::class, function () use ($app) {
            return new Client(['handler' => $app->make(HandlerStack::class)]);
        });

        $app->alias(ClientInterface::class, Client::class);

        $app->bind(HandlerStack::class, function () use ($monitor) {
            return tap(HandlerStack::create(), function ($stack) use ($monitor) {
                $stack->unshift(GuzzleReporter::create($monitor));
            });
        });
    }
}

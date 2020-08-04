<?php

namespace Jhoff\RequestMonitor;

use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Util\Test as TestUtil;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Jhoff\RequestMonitor\RequestMonitor;

class RequestMonitorExtension implements BeforeFirstTestHook, AfterTestHook, AfterLastTestHook
{
    /**
     * Request monitoring enabled by default. Set to false to disable.
     *
     * Use environment variable "PHPUNIT_REQUEST_MONITOR" set to value "disabled" to
     * disable monitoring.
     *
     * @var boolean
     */
    protected $enabled = true;

    /**
     * Collected data from all tracked requests
     *
     * @var array
     */
    protected $requests = [];

    /**
     * Providers to pass along to the request monitor
     *
     * @var array
     */
    protected $providers;

    /**
     * Intantiate a new request monitor listener
     *
     * @param array $providers
     */
    public function __construct(array $providers = [])
    {
        $this->enabled = getenv('PHPUNIT_REQUEST_MONITOR') === 'disabled' ? false : true;
        $this->providers = $providers;
    }

    public function executeBeforeFirstTest(): void
    {
        if (!$this->enabled) return;

        RequestMonitor::initialize($this->providers);
    }

    public function executeAfterTest(string $test, float $time): void
    {
        if (!$this->enabled) return;

        if ($requests = RequestMonitor::flush()) {
            $this->requests[$test] = $requests;
        }
    }

    public function executeAfterLastTest(): void
    {
        if (!$this->enabled) return;

        echo sprintf(
            "\nExternal Http Requests %s\n",
            $this->format($this->total($this->requests))
        );

        foreach ($this->requests as $name => $requests) {
            echo sprintf(
                "\n   %s %s\n",
                $name,
                $this->format($this->total($requests))
            );

            foreach ($requests as $request) {
                echo sprintf(
                    "      %s - (%d) %s %s %s\n",
                    $request->type,
                    $request->responseCode,
                    $request->method,
                    $request->url,
                    $this->format($request->duration)
                );
            }
        }
    }

    protected function total($input)
    {
        if ($input instanceof Request) {
            return $input->duration;
        }

        $total = 0;
        foreach($input as $item) {
            $total += $this->total($item);
        }
        return $total;
    }

    protected function format(int $microseconds)
    {
        if ($microseconds > 60000) {
            $seconds = round($microseconds / 60000);
            return sprintf('%d:%02d', $seconds, round(($microseconds / 1000) % 60));
        }

        if ($microseconds > 1000) {
            return round($microseconds / 1000, 3) . 's';
        }

        return $microseconds . 'ms';
    }
}


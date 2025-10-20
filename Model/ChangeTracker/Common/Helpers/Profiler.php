<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class Profiler
{
    protected ?float $startTime = null;
    protected float $time;

    private int $precision;

    public function __construct(int $precision = 4)
    {
        $this->precision = $precision;
    }

    public function start(): void
    {
        if ($this->startTime !== null) {
            throw new \RuntimeException('Profiler is already running.');
        }
        $this->startTime = microtime(true);
    }

    public function stop(): void
    {
        $this->time = (float)number_format((microtime(true) - $this->startTime), $this->precision);
        $this->startTime = null;
    }

    public function getTime(): float
    {
        return $this->time;
    }
}

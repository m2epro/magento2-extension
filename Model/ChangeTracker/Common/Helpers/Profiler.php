<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class Profiler
{
    /** @var float */
    protected $startTime;
    /** @var float seconds */
    protected $time;

    /** @var int */
    private $precision;

    /**
     * @param int $precision
     */
    public function __construct(int $precision = 4)
    {
        $this->precision = $precision;
    }

    /**
     * @return void
     */
    public function start(): void
    {
        if ($this->startTime !== null) {
            throw new \RuntimeException('Profiler is already running.');
        }
        $this->startTime = microtime(true);
    }

    /**
     * @return void
     */
    public function stop(): void
    {
        $this->time = (float)number_format((microtime(true) - $this->startTime), $this->precision);
        $this->startTime = null;
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function logString(): string
    {
        return sprintf(
            'Executed time: %s sec',
            $this->getTime()
        );
    }
}

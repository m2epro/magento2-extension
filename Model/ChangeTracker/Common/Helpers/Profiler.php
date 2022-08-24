<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class Profiler
{
    /** @var float */
    protected $startTime = 0.0;
    /** @var float */
    protected $memoryStart = 0.0;
    /** @var float */
    protected $memoryPeakStart = 0.0;

    /** @var float seconds */
    protected $time;
    /** @var float bytes */
    protected $memory;
    /** @var float bytes */
    protected $memoryPeak;

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
        $this->startTime = microtime(true);
        $this->memoryStart = $this->getMemoryUsage();
        $this->memoryPeakStart = $this->getMemoryPeakUsage();
    }

    /**
     * @return void
     */
    public function end(): void
    {
        $this->time = (float)number_format((microtime(true) - $this->startTime), $this->precision);
        $this->memory = (float)number_format($this->getMemoryUsage() - $this->memoryStart, $this->precision);
        $this->memoryPeak = (float)number_format($this->getMemoryPeakUsage() - $this->memoryStart, $this->precision);
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
    public function getMemory(): string
    {
        return $this->memory;
    }

    /**
     * @return string
     */
    public function getMemoryPeak(): string
    {
        return $this->memoryPeak;
    }

    /**
     * @return string
     */
    public function logString(): string
    {
        return sprintf(
            'Executed time: %s sec; Memory: %s; Peak Memory: %s.',
            $this->getTime(),
            $this->humanReadableSize($this->getMemory()),
            $this->humanReadableSize($this->getMemoryPeak())
        );
    }

    /**
     * @return float
     */
    private function getMemoryUsage(): float
    {
        return (float)(memory_get_usage(false) / (1024));
    }

    /**
     * @return float
     */
    private function getMemoryPeakUsage(): float
    {
        return (float)(memory_get_peak_usage(false) / (1024));
    }

    /**
     * @param float $size
     *
     * @return string
     */
    private function humanReadableSize(float $size): string
    {
        if ($size === 0.0) {
            return '0 b';
        }

        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return round($size / (1024 ** ($i = floor(abs(log($size, 1024))))), 2) . ' ' . $unit[$i];
    }
}

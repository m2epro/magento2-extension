<?php

namespace Ess\M2ePro\Model\Amazon\Dashboard\Errors;

class CachedCalculator implements \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface
{
    private const CACHE_LIFE_TIME = 600; // 10 min

    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\Errors\Calculator */
    private $calculator;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cache;

    public function __construct(Calculator $calculator, \Ess\M2ePro\Helper\Data\Cache\Permanent $cache)
    {
        $this->calculator = $calculator;
        $this->cache = $cache;
    }

    public function getCountForToday(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getCountForToday();
        });
    }

    public function getCountForYesterday(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getCountForYesterday();
        });
    }

    public function getCountFor2DaysAgo(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getCountFor2DaysAgo();
        });
    }

    public function getTotalCount(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getTotalCount();
        });
    }

    private function getCachedValue(string $key, callable $handler): int
    {
        /** @var int|null $cachedValue */
        if ($cachedValue = $this->cache->getValue($key)) {
            return $cachedValue;
        }

        /** @var int $value */
        $value = $handler();
        $this->cache->setValue($key, $value, [], self::CACHE_LIFE_TIME);

        return $value;
    }
}

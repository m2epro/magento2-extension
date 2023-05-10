<?php

namespace Ess\M2ePro\Model\Walmart\Dashboard\Shipments;

class CachedCalculator implements \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface
{
    private const CACHE_LIFE_TIME = 600; // 10 min

    /** @var \Ess\M2ePro\Model\Walmart\Dashboard\Shipments\Calculator */
    private $calculator;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cache;

    public function __construct(Calculator $calculator, \Ess\M2ePro\Helper\Data\Cache\Permanent $cache)
    {
        $this->calculator = $calculator;
        $this->cache = $cache;
    }

    public function getCountOfLateShipments(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getCountOfLateShipments();
        });
    }

    public function getCountByOver2Days(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getCountByOver2Days();
        });
    }

    public function getCountForToday(): int
    {
        return $this->getCachedValue(__METHOD__, function () {
            return $this->calculator->getCountForToday();
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

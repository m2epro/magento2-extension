<?php

namespace Ess\M2ePro\Model\Amazon\Dashboard\Sales;

class CachedCalculator implements \Ess\M2ePro\Model\Dashboard\Sales\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\Sales\Calculator */
    private $calculator;
    /** @var \Ess\M2ePro\Model\Dashboard\Sales\Cache */
    private $cache;

    public function __construct(Calculator $calculator, \Ess\M2ePro\Model\Dashboard\Sales\Cache $cache)
    {
        $this->calculator = $calculator;
        $this->cache = $cache;
    }

    public function getAmountPointSetFor24Hours(): \Ess\M2ePro\Model\Dashboard\Sales\PointSet
    {
        return $this->cache->getCachedPointSet(__METHOD__, function () {
            return $this->calculator->getAmountPointSetFor24Hours();
        });
    }

    public function getQtyPointSetFor24Hours(): \Ess\M2ePro\Model\Dashboard\Sales\PointSet
    {
        return $this->cache->getCachedPointSet(__METHOD__, function () {
            return $this->calculator->getQtyPointSetFor24Hours();
        });
    }

    public function getAmountPointSetFor7Days(): \Ess\M2ePro\Model\Dashboard\Sales\PointSet
    {
        return $this->cache->getCachedPointSet(__METHOD__, function () {
            return $this->calculator->getAmountPointSetFor7Days();
        });
    }

    public function getQtyPointSetFor7Days(): \Ess\M2ePro\Model\Dashboard\Sales\PointSet
    {
        return $this->cache->getCachedPointSet(__METHOD__, function () {
            return $this->calculator->getQtyPointSetFor7Days();
        });
    }

    public function getAmountPointSetForToday(): \Ess\M2ePro\Model\Dashboard\Sales\PointSet
    {
        return $this->cache->getCachedPointSet(__METHOD__, function () {
            return $this->calculator->getAmountPointSetForToday();
        });
    }

    public function getQtyPointSetForToday(): \Ess\M2ePro\Model\Dashboard\Sales\PointSet
    {
        return $this->cache->getCachedPointSet(__METHOD__, function () {
            return $this->calculator->getQtyPointSetForToday();
        });
    }
}

<?php

namespace Ess\M2ePro\Model\Amazon\Dashboard\ListingProductIssues;

class CachedCalculator implements \Ess\M2ePro\Model\Dashboard\ListingProductIssues\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\ListingProductIssues\Calculator */
    private $calculator;
    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\Cache */
    private $cache;

    public function __construct(
        Calculator $calculator,
        \Ess\M2ePro\Model\Dashboard\ListingProductIssues\Cache $cache
    ) {
        $this->calculator = $calculator;
        $this->cache = $cache;
    }

    public function getTopIssues(): \Ess\M2ePro\Model\Dashboard\ListingProductIssues\IssueSet
    {
        return $this->cache->getCachedIssueSet(__METHOD__, function () {
            return $this->calculator->getTopIssues();
        });
    }
}

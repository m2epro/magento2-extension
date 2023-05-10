<?php

namespace Ess\M2ePro\Model\Ebay\Dashboard\Errors;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Repository */
    private $repository;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory $dateRangeFactory,
        \Ess\M2ePro\Model\Tag\ListingProduct\Repository $repository
    ) {
        $this->dateRangeFactory = $dateRangeFactory;
        $this->repository = $repository;
    }

    public function getCountForToday(): int
    {
        $dateRange = $this->dateRangeFactory->createForToday();
        return $this->repository->getCountOfErrorTagsForPeriod(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $dateRange->getDateStart(),
            $dateRange->getDateEnd()
        );
    }

    public function getCountForYesterday(): int
    {
        $dateRange = $this->dateRangeFactory->createForYesterday();
        return $this->repository->getCountOfErrorTagsForPeriod(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $dateRange->getDateStart(),
            $dateRange->getDateEnd()
        );
    }

    public function getCountFor2DaysAgo(): int
    {
        $dateRange = $this->dateRangeFactory->createFor2DaysAgo();
        return $this->repository->getCountOfErrorTagsForPeriod(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $dateRange->getDateStart(),
            $dateRange->getDateEnd()
        );
    }

    public function getTotalCount(): int
    {
        return $this->repository->getTotalCountOfErrorTags(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }
}

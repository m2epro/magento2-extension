<?php

namespace Ess\M2ePro\Model\Walmart\Dashboard\Errors;

use Ess\M2ePro\Model\Dashboard\Date\DateRange;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Errors\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log\CollectionFactory */
    private $listingLogCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory $dateRangeFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log\CollectionFactory $listingLogCollectionFactory
    ) {
        $this->dateRangeFactory = $dateRangeFactory;
        $this->listingLogCollectionFactory = $listingLogCollectionFactory;
    }

    public function getCountForToday(): int
    {
        $todayDateRange = $this->dateRangeFactory->createForToday();

        return $this->getQuantityAmazonLogs($todayDateRange);
    }

    public function getCountForYesterday(): int
    {
        $yesterdayDateRange = $this->dateRangeFactory->createForYesterday();

        return $this->getQuantityAmazonLogs($yesterdayDateRange);
    }

    public function getCountFor2DaysAgo(): int
    {
        $dateRange = $this->dateRangeFactory->createFor2DaysAgo();

        return $this->getQuantityAmazonLogs($dateRange);
    }

    public function getTotalCount(): int
    {
        return $this->getQuantityAmazonLogs();
    }

    private function getQuantityAmazonLogs(DateRange $dateRange = null): int
    {
        $listingLogCollection = $this->listingLogCollectionFactory->create();
        $select = $listingLogCollection->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*)');
        $select->where('component_mode = ?', \Ess\M2ePro\Helper\Component\Walmart::NICK);
        $select->where('type = ?', \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR);

        if ($dateRange) {
            $select->where(
                sprintf(
                    "create_date BETWEEN '%s' AND '%s'",
                    $dateRange->getDateStart()->format('Y-m-d H:i:s'),
                    $dateRange->getDateEnd()->format('Y-m-d H:i:s')
                )
            );
        }

        return (int)$listingLogCollection->getConnection()->fetchOne($select);
    }
}

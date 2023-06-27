<?php

namespace Ess\M2ePro\Model\Ebay\Dashboard\Errors;

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

        return $this->getQuantityEbayLogs($todayDateRange);
    }

    public function getCountForYesterday(): int
    {
        $yesterdayDateRange = $this->dateRangeFactory->createForYesterday();

        return $this->getQuantityEbayLogs($yesterdayDateRange);
    }

    public function getCountFor2DaysAgo(): int
    {
        $dateRange = $this->dateRangeFactory->createFor2DaysAgo();

        return $this->getQuantityEbayLogs($dateRange);
    }

    public function getTotalCount(): int
    {
        return $this->getQuantityEbayLogs();
    }

    private function getQuantityEbayLogs(DateRange $dateRange = null): int
    {
        $listingLogCollection = $this->listingLogCollectionFactory->create();
        $listingLogCollection->skipIncorrectAccounts();
        $listingLogCollection->skipIncorrectMarketplaces();
        $select = $listingLogCollection->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*)');
        $select->where('main_table.component_mode = ?', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $select->where('main_table.type = ?', \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR);

        if ($dateRange) {
            $select->where(
                sprintf(
                    "main_table.create_date BETWEEN '%s' AND '%s'",
                    $dateRange->getDateStart()->format('Y-m-d H:i:s'),
                    $dateRange->getDateEnd()->format('Y-m-d H:i:s')
                )
            );
        }

        $select->group(['main_table.listing_product_id', 'main_table.description']);

        return $listingLogCollection->getSize();
    }
}

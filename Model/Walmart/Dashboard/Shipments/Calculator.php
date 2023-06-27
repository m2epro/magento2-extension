<?php

namespace Ess\M2ePro\Model\Walmart\Dashboard\Shipments;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Order\CollectionFactory */
    private $resourceCollectionFactory;
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Order\CollectionFactory $resourceCollectionFactory,
        \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory $dateRangeFactory
    ) {
        $this->resourceCollectionFactory = $resourceCollectionFactory;
        $this->dateRangeFactory = $dateRangeFactory;
    }

    public function getCountOfLateShipments(): int
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where('shipping_date_to < ?', $currentDate->format('Y-m-d H:i:s'));
        $select->where('status = ?', \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED);

        return (int)$select->query()->fetchColumn();
    }

    public function getCountOfShipByToday(): int
    {
        $dateRange = $this->dateRangeFactory->createForToday();
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where(
            sprintf(
                "shipping_date_to BETWEEN '%s' AND '%s'",
                $currentDate->format('Y-m-d H:i:s'),
                $dateRange->getDateEnd()->format('Y-m-d H:i:s')
            )
        );
        $select->where('status = ?', \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED);

        return (int)$select->query()->fetchColumn();
    }

    public function getCountOfShipByTomorrow(): int
    {
        $dateRange = $this->dateRangeFactory->createForTomorrow();

        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where(
            sprintf(
                "shipping_date_to BETWEEN '%s' AND '%s'",
                $dateRange->getDateStart()->format('Y-m-d H:i:s'),
                $dateRange->getDateEnd()->format('Y-m-d H:i:s')
            )
        );
        $select->where('status = ?', \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED);

        return (int)$select->query()->fetchColumn();
    }

    public function getCountForTwoAndMoreDays(): int
    {
        $dateRange = $this->dateRangeFactory->createForTwoAndMoreDays();

        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where('shipping_date_to >= ?', $dateRange->getDateStart()->format('Y-m-d H:i:s'));
        $select->where('status = ?', \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED);

        return (int)$select->query()->fetchColumn();
    }
}

<?php

namespace Ess\M2ePro\Model\Amazon\Dashboard\Shipments;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order\CollectionFactory */
    private $resourceCollectionFactory;
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Order\CollectionFactory $resourceCollectionFactory,
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
        $select->where('status = ?', \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED);

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }

    public function getCountByOver2Days(): int
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $dateIn2Days = $currentDate->modify('+2 days');

        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where('shipping_date_to >= ?', $dateIn2Days->format('Y-m-d H:i:s'));
        $select->where('status = ?', \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED);

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }

    public function getCountForToday(): int
    {
        $dateRange = $this->dateRangeFactory->createForToday();

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
        $select->where('status = ?', \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED);

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }

    public function getTotalCount(): int
    {
        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where('status = ?', \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED);

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }
}

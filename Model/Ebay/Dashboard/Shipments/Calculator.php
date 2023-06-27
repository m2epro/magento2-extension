<?php

namespace Ess\M2ePro\Model\Ebay\Dashboard\Shipments;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Shipments\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Order\CollectionFactory */
    private $resourceCollectionFactory;
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Order\CollectionFactory $resourceCollectionFactory,
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
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where('cancellation_status = 0');

        return (int)$select->query()->fetchColumn();
    }

    public function getCountOfShipByToday(): int
    {
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $dateRange = $this->dateRangeFactory->createForToday();

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
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where('cancellation_status = 0');

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
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where('cancellation_status = 0');

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
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where('cancellation_status = 0');

        return (int)$select->query()->fetchColumn();
    }
}

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
        $select->where(
            'cancellation_status = ?',
            \Ess\M2ePro\Model\Ebay\Order::BUYER_CANCELLATION_STATUS_NOT_REQUESTED
        );

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
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where(
            'cancellation_status = ?',
            \Ess\M2ePro\Model\Ebay\Order::BUYER_CANCELLATION_STATUS_NOT_REQUESTED
        );

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
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where(
            'cancellation_status = ?',
            \Ess\M2ePro\Model\Ebay\Order::BUYER_CANCELLATION_STATUS_NOT_REQUESTED
        );

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }

    public function getTotalCount(): int
    {
        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns('COUNT(*) AS value');
        $select->where('shipping_date_to IS NOT NULL');
        $select->where('shipping_status != ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED);
        $select->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED);
        $select->where(
            'cancellation_status = ?',
            \Ess\M2ePro\Model\Ebay\Order::BUYER_CANCELLATION_STATUS_NOT_REQUESTED
        );

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }
}

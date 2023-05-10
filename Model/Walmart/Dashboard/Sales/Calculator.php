<?php

namespace Ess\M2ePro\Model\Walmart\Dashboard\Sales;

use Ess\M2ePro\Model\Dashboard\Date\DateRange;
use Ess\M2ePro\Model\Dashboard\Sales\PointSet;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Sales\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory */
    private $dateRangeFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Order\CollectionFactory */
    private $resourceCollectionFactory;
    /** @var \Ess\M2ePro\Model\Dashboard\Sales\PointFactory */
    private $pointFactory;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory $dateRangeFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Order\CollectionFactory $resourceCollectionFactory,
        \Ess\M2ePro\Model\Dashboard\Sales\PointFactory $pointFactory
    ) {
        $this->dateRangeFactory = $dateRangeFactory;
        $this->resourceCollectionFactory = $resourceCollectionFactory;
        $this->pointFactory = $pointFactory;
    }

    public function getAmountPointSetFor24Hours(): PointSet
    {
        $dateRange = $this->dateRangeFactory->createForLast24Hours();

        return $this->getAmountPointSet($dateRange, true);
    }

    public function getQtyPointSetFor24Hours(): PointSet
    {
        $dateRange = $this->dateRangeFactory->createForLast24Hours();

        return $this->getQuantityPointSet($dateRange, true);
    }

    public function getAmountPointSetFor7Days(): PointSet
    {
        $dateRange = $this->dateRangeFactory->createForLast7Days();

        return $this->getAmountPointSet($dateRange, false);
    }

    public function getQtyPointSetFor7Days(): PointSet
    {
        $dateRange = $this->dateRangeFactory->createForLast7Days();

        return $this->getQuantityPointSet($dateRange, false);
    }

    public function getAmountPointSetForToday(): PointSet
    {
        $dateRange = $this->dateRangeFactory->createForToday();

        return $this->getAmountPointSet($dateRange, true);
    }

    public function getQtyPointSetForToday(): PointSet
    {
        $dateRange = $this->dateRangeFactory->createForToday();

        return $this->getQuantityPointSet($dateRange, true);
    }

    private function getAmountPointSet(DateRange $dateRange, bool $isHourlyInterval): PointSet
    {
        return $this->getPointSet('SUM(paid_amount)', $dateRange, $isHourlyInterval);
    }

    private function getQuantityPointSet(DateRange $dateRange, bool $isHourlyInterval): PointSet
    {
        return $this->getPointSet('COUNT(*)', $dateRange, $isHourlyInterval);
    }

    private function getPointSet(string $valueColumn, DateRange $dateRange, bool $isHourlyInterval): PointSet
    {
        $select = $this->resourceCollectionFactory->create()->getSelect();
        $select->reset('columns');
        $select->columns(
            [
                'purchase_update_date AS date',
                sprintf('%s AS value', $valueColumn),
            ]
        );
        $select->where(
            sprintf(
                'status = %s OR status = %s OR status = %s',
                \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED,
                \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED,
                \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY
            )
        );
        $select->where(
            sprintf(
                "purchase_update_date BETWEEN '%s' AND '%s'",
                $dateRange->getDateStart()->format('Y-m-d H:i:s'),
                $dateRange->getDateEnd()->format('Y-m-d H:i:s')
            )
        );
        if ($isHourlyInterval) {
            $select->group('HOUR(main_table.purchase_update_date)');
        }
        $select->group('DAY(main_table.purchase_update_date)');
        $select->order('date');

        $queryData = $select->query()->fetchAll();

        $set = $this->pointFactory->createSet();
        foreach ($queryData as $dataItem) {
            $point = $this->pointFactory->createPoint((float)$dataItem['value'], $dataItem['date']);
            $set->addPoint($point);
        }

        return $set;
    }
}

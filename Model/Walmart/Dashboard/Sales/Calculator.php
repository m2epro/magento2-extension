<?php

namespace Ess\M2ePro\Model\Walmart\Dashboard\Sales;

use Ess\M2ePro\Model\Dashboard\Date\DateRange;
use Ess\M2ePro\Model\Dashboard\Sales\PointSet;
use Ess\M2ePro\Model\ResourceModel\Walmart\Order as WalmartOrderResource;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Sales\CalculatorInterface
{
    private \Ess\M2ePro\Model\Dashboard\Date\DateRangeFactory $dateRangeFactory;
    private WalmartOrderResource\CollectionFactory $resourceCollectionFactory;
    private \Ess\M2ePro\Model\Dashboard\Sales\PointFactory $pointFactory;

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
                sprintf(
                    'DATE_FORMAT(%s, "%s") AS date',
                    WalmartOrderResource::COLUMN_PURCHASE_CREATE_DATE,
                    $isHourlyInterval ? '%Y-%m-%d %H' : '%Y-%m-%d'
                ),
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
                "%s BETWEEN '%s' AND '%s'",
                WalmartOrderResource::COLUMN_PURCHASE_CREATE_DATE,
                $dateRange->getDateStart()->format('Y-m-d H:i:s'),
                $dateRange->getDateEnd()->format('Y-m-d H:i:s')
            )
        );
        if ($isHourlyInterval) {
            $select->group(sprintf('HOUR(main_table.%s)', WalmartOrderResource::COLUMN_PURCHASE_CREATE_DATE));
        }
        $select->group(sprintf('DAY(main_table.%s)', WalmartOrderResource::COLUMN_PURCHASE_CREATE_DATE));
        $select->order('date');

        $queryData = $select->query()->fetchAll();

        $keyValueData = array_combine(
            array_column($queryData, 'date'),
            array_column($queryData, 'value')
        );

        return $this->makePointSet($keyValueData, $dateRange, $isHourlyInterval);
    }

    private function makePointSet(array $data, DateRange $dateRange, bool $isHourlyInterval): PointSet
    {
        $intervalFormat = $isHourlyInterval ? 'PT1H' : 'P1D';
        $dateFormat = $isHourlyInterval ? 'Y-m-d H' : 'Y-m-d';

        $period = new \DatePeriod(
            $dateRange->getDateStart(),
            new \DateInterval($intervalFormat),
            $dateRange->getDateEnd()
        );

        $pointSet = $this->pointFactory->createSet();
        foreach ($period as $value) {
            $pointValue = $data[$value->format($dateFormat)] ?? 0;
            $point = $this->pointFactory->createPoint($pointValue, $value->format('Y-m-d H:i:s'));
            $pointSet->addPoint($point);
        }

        return $pointSet;
    }
}

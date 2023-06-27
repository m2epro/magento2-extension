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
                sprintf(
                    'DATE_FORMAT(purchase_update_date, "%s") AS date',
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

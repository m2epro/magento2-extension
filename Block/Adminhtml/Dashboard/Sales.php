<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

use Ess\M2ePro\Model\Dashboard\Sales\PointSet;

class Sales extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private const DATE_PERIOD_REQUEST_KEY = 'sales_period';
    private const TODAY_PERIOD_VALUE = 'today';
    private const LAST_24_HOURS_PERIOD_VALUE = '24h';
    private const LAST_7_DAYS_PERIOD_VALUE = '7d';

    private $defaultActivePeriodVal = self::TODAY_PERIOD_VALUE;
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/sales.phtml';
    /** @var \Ess\M2ePro\Model\Dashboard\Sales\CalculatorInterface */
    private $calculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Sales\TabsFactory */
    private $tabsFactory;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Sales\CalculatorInterface $calculator,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Sales\TabsFactory $tabsFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->calculator = $calculator;
        $this->tabsFactory = $tabsFactory;
    }

    public function getTabs(): Sales\Tabs
    {
        $activePeriod = $this->getActivePeriod();
        $pointSets = $this->getPointSets($activePeriod);

        $qtyTabItem = $this->tabsFactory->createTabItem(
            __('Quantity'),
            $pointSets['qty'],
            $this->getLayout()
        );

        $amountsTabItem = $this->tabsFactory->createTabItem(
            __('Amounts'),
            $pointSets['amount'],
            $this->getLayout()
        );

        if (
            $activePeriod === self::LAST_24_HOURS_PERIOD_VALUE
            || $activePeriod === self::TODAY_PERIOD_VALUE
        ) {
            $qtyTabItem->enableHourlyShowingMode();
            $amountsTabItem->enableHourlyShowingMode();
        }

        return $this->tabsFactory->createTabs($amountsTabItem, $qtyTabItem, $this->getLayout());
    }

    /**
     * @return array{amount: PointSet, qty: PointSet}
     */
    private function getPointSets(string $period): array
    {
        if ($period === self::LAST_24_HOURS_PERIOD_VALUE) {
            return [
                'amount' => $this->calculator->getAmountPointSetFor24Hours(),
                'qty' => $this->calculator->getQtyPointSetFor24Hours(),
            ];
        }

        if ($period === self::LAST_7_DAYS_PERIOD_VALUE) {
            return [
                'amount' => $this->calculator->getAmountPointSetFor7Days(),
                'qty' => $this->calculator->getQtyPointSetFor7Days(),
            ];
        }

        return [
            'amount' => $this->calculator->getAmountPointSetForToday(),
            'qty' => $this->calculator->getQtyPointSetForToday(),
        ];
    }

    private function getActivePeriod(): string
    {
        if ($period = $this->getRequest()->getParam(self::DATE_PERIOD_REQUEST_KEY)) {
            return $period;
        }

        return $this->defaultActivePeriodVal;
    }

    /**
     * @return array<array{value:string, title:string, is_active:bool}>
     */
    public function getDatePeriods(): array
    {
        $periods = [
            ['title' => __('Today'), 'value' => self::TODAY_PERIOD_VALUE,],
            ['title' => __('Last 24 hours'), 'value' => self::LAST_24_HOURS_PERIOD_VALUE],
            ['title' => __('Last 7 days'), 'value' => self::LAST_7_DAYS_PERIOD_VALUE],
        ];

        $activePeriod = $this->getActivePeriod();

        return array_map(function (array $period) use ($activePeriod) {
            $isActive = $period['value'] === $activePeriod;

            return array_merge($period, ['is_active' => $isActive]);
        }, $periods);
    }

    protected function _toHtml()
    {
        $url = $this->getUrl('*/*/*') . self::DATE_PERIOD_REQUEST_KEY;

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Dashboard/Sales/DateSwitcher'
    ], function(){
        var dateSwitcher = new DashboardSalesDateSwitcher();
        dateSwitcher.initObservers('$url');
    });
JS
        );

        return parent::_toHtml();
    }
}

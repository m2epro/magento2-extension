<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard\Sales\Tabs;

class Item extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/sales/tab.phtml';
    /** @var string */
    private $label;
    /** @var \Ess\M2ePro\Model\Dashboard\Sales\PointSet */
    private $pointSet;
    /** @var bool */
    private $isHourlyShowingModeEnabled = false;

    public function __construct(
        $label,
        \Ess\M2ePro\Model\Dashboard\Sales\PointSet $pointSet,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->label = $label;
        $this->pointSet = $pointSet;
    }

    protected function _beforeToHtml()
    {
        $points = $this->pointSet->getPoints();
        $chartId = $this->getChartIdentifier();

        if (empty($points)) {
            $this->jsTranslator->addTranslations([
                'No Data' => __('No Data'),
            ]);

            $js = <<<JS
require([
    'M2ePro/ChartJs/Bar'
], function() {
    var chart = new Bar();
    chart.renderWithoutData('#$chartId', '$this->label')
});
JS;
            $this->js->add($js);

            return parent::_beforeToHtml();
        }

        $dateFormat = $this->isHourlyShowingModeEnabled ? 'Y-m-d H:i' : 'Y-m-d';
        $dateTimeZone = new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getConfigTimezone());

        $chartData = array_map(function ($point) use ($dateTimeZone, $dateFormat) {
            $date = $point->getDate();
            $date = $date->setTimezone($dateTimeZone);
            $dateStr = $date->format($dateFormat);

            return [
                'x' => $dateStr,
                'y' => round($point->getValue(), 2),
            ];
        }, $points);

        $dataset = json_encode($chartData);

        $js = <<<JS
require([
    'M2ePro/ChartJs/Bar'
], function() {
    var chart = new Bar();
    chart.renderWithData('#$chartId', $dataset, '$this->label')
});
JS;
        $this->js->add($js);

        return parent::_beforeToHtml();
    }

    public function getChartIdentifier(): string
    {
        return sprintf('dashboard_sales_tabs_%s_tab', strtolower($this->label));
    }

    public function enableHourlyShowingMode(): void
    {
        $this->isHourlyShowingModeEnabled = true;
    }
}

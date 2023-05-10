<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class Products extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard/products.phtml';
    /** @var \Ess\M2ePro\Model\Dashboard\Products\CalculatorInterface */
    private $calculator;

    public function __construct(
        \Ess\M2ePro\Model\Dashboard\Products\CalculatorInterface $calculator,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->calculator = $calculator;
    }

    protected function _beforeToHtml()
    {
        $active = $this->calculator->getCountOfActiveProducts();
        $inactive = $this->calculator->getCountOfInactiveProducts();
        $total = $active + $inactive;

        if ($total === 0) {
            $this->jsTranslator->addTranslations([
                'No Data' => __('No Data'),
            ]);

            $js = <<<JS
require([
    'M2ePro/ChartJs/Doughnut'
], function() {
    var chart = new Doughnut();
    chart.renderWithoutData()
});
JS;
            $this->js->add($js);

            return parent::_beforeToHtml();
        }

        $chartDataset = [
            'labels' => [
                __('Active'),
                __('Inactive'),
            ],
            'data' => [
                $active,
                $inactive,
            ],
            'backgroundColor' => [
                '#f1d4b3',
                '#a9b4b8',
            ],
        ];
        $chartDatasetJson = json_encode($chartDataset);

        $js = <<<JS
require([
    'M2ePro/ChartJs/Doughnut'
], function() {
    var chart = new Doughnut();
    chart.renderWithData($chartDatasetJson)
});
JS;

        $this->js->add($js);

        return parent::_beforeToHtml();
    }
}

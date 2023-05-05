<?php

namespace Ess\M2ePro\Block\Adminhtml;

class DashboardDemo extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string  */
    protected $_template = 'Ess_M2ePro::dashboard-demo.phtml';

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        $this->css->addFile('dashboard-demo/view.css');

        return parent::_prepareLayout();
    }

    /**
     * @return array<int, array{url: string, alt: string, style: string}>
     */
    public function getCharts(): array
    {
        return [
            [
                'url' => $this->getViewFileUrl('Ess_M2ePro::images/dashboard_demo/total-sales-chart.svg'),
                'alt' => __('Total sales'),
                'style' => 'width: 44%;',
            ],
            [
                'url' => $this->getViewFileUrl('Ess_M2ePro::images/dashboard_demo/total-orders-chart.svg'),
                'alt' => __('Total orders'),
                'style' => 'width: 17%;',
            ],
            [
                'url' => $this->getViewFileUrl('Ess_M2ePro::images/dashboard_demo/errors-info.svg'),
                'alt' => __('Errors info'),
                'style' => 'width: 17%;',
            ],
        ];
    }
}

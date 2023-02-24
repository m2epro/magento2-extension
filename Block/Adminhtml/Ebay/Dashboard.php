<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

class Dashboard extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string  */
    protected $_template = 'Ess_M2ePro::ebay/dashboard.phtml';

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/dashboard/view.css');

        return parent::_prepareLayout();
    }

    /**
     * @return array<int, array{url: string, alt: string, style: string}>
     */
    public function getCharts(): array
    {
        return [
            [
                'url' => $this->getViewFileUrl('Ess_M2ePro::images/ebay/dashboard/total-sales-chart.svg'),
                'alt' => __('Total sales'),
                'style' => 'width: 44%;',
            ],
            [
                'url' => $this->getViewFileUrl('Ess_M2ePro::images/ebay/dashboard/total-orders-chart.svg'),
                'alt' => __('Total orders'),
                'style' => 'width: 17%;',
            ],
            [
                'url' => $this->getViewFileUrl('Ess_M2ePro::images/ebay/dashboard/errors-info.svg'),
                'alt' => __('Errors info'),
                'style' => 'width: 17%;',
            ],
        ];
    }
}

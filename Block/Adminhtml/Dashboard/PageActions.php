<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class PageActions extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    public const BLOCK_PATH = 'Dashboard_PageActions';

    /** @var \Ess\M2ePro\Helper\Analytics */
    private $analytics;

    public function __construct(
        \Ess\M2ePro\Helper\Analytics $analytics,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->analytics = $analytics;
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('dashboard/page-actions.css');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $button */
        $button = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);

        $url =  $this->analytics->getUrl();
        $button->setData(
            [
                'label' => __('Analytics'),
                'onclick' => sprintf("window.open('%s', '_blank')", $url),
                'class' => 'action-btn primary external-link',
            ]
        );

        return $button->toHtml();
    }
}

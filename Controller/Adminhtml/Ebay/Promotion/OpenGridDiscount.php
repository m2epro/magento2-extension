<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Promotion;

class OpenGridDiscount extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Helper\Data\GlobalData $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $this->globalData->setValue(
            'promotionId',
            $this->getRequest()->getParam('promotion_id')
        );

        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Promotion\Discount\Grid::class
        );

        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }
}

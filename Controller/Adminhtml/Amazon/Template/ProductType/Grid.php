<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Grid $grid */
        $grid = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Grid::class);

        $this->setAjaxContent($grid->toHtml());

        return $this->getResult();
    }
}

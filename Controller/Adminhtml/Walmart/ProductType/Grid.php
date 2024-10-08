<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Grid $grid */
        $grid = $this->getLayout()
                     ->createBlock(
                         \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Grid::class
                     );

        $this->setAjaxContent($grid->toHtml());

        return $this->getResult();
    }
}

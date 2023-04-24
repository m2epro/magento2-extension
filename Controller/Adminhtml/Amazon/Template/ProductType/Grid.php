<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Grid $grid */
        $grid = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Grid::class);

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}

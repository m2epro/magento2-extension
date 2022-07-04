<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\ViewGrid
 */
class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View\Grid::class)
        );
        return $this->getResult();
    }
}

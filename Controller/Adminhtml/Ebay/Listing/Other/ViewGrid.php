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
        $block = $this->createBlock(
            'Ebay_Listing_Other_View_Grid'
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\GetChangePartsCompatibilityModePopupHtml
 */
class GetChangePartsCompatibilityModePopupHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Motor\EditMode $block */
        $block = $this->createBlock('Ebay_Listing_Grid_Motor_EditMode');
        $block->setListingId($this->getRequest()->getParam('listing_id'));

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}

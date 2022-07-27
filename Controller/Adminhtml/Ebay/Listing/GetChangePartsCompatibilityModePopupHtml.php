<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetChangePartsCompatibilityModePopupHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Motor\EditMode $block */
        $block = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Motor\EditMode::class);
        $block->setListingId($this->getRequest()->getParam('listing_id'));

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}

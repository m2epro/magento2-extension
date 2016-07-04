<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

class GetCategoryChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $groupId = $this->getRequest()->getParam('group_id');
        $autoMode  = $this->getRequest()->getParam('auto_mode');
        $listing   = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        $template = $this->getCategoryTemplate($autoMode, $groupId, $listing);
        $otherTemplate = $this->getOtherCategoryTemplate($autoMode, $groupId, $listing);

        /* @var $chooserBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser */
        $chooserBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser');

        if ($autoMode == \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY) {
            $chooserBlock->setDivId('category_child_data_container');
        } else {
            $chooserBlock->setDivId('data_container');
        }
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());

        if (!is_null($template)) {
            $data = $template->getData();
            $otherTemplate && $data = array_merge($data, $otherTemplate->getData());

            $chooserBlock->setInternalData($data);
        }

        $this->setAjaxContent($chooserBlock);
        return $this->getResult();
    }

    //########################################
}
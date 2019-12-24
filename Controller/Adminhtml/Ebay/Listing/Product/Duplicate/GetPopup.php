<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Duplicate;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Duplicate\GetPopup
 */
class GetPopup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId, null, false);

        if ($listingProduct === null) {
            $this->setJsonContent([
                'error' => $this->__("Unable to load product ID [{$listingProductId}].")
            ]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\ItemDuplicate $block */
        $block = $this->createBlock('Ebay_Listing_View_Ebay_ItemDuplicate');
        $block->setListingProduct($listingProduct);

        $this->setJsonContent([
            'html' => $block->toHtml()
        ]);
        return $this->getResult();
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search\GetSearchAsinMenu
 */
class GetSearchAsinMenu extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('ERROR: No Product ID!', false);

            return $this->getResult();
        }

        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        $productSearchMenuBlock = $this->createBlock('Amazon_Listing_Product_Search_Menu');
        $productSearchMenuBlock->setListingProduct($listingProduct);

        $this->setAjaxContent($productSearchMenuBlock);

        return $this->getResult();
    }
}

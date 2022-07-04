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

        $productSearchMenuBlock = $this->getLayout()
                                   ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search\Menu::class);
        $productSearchMenuBlock->setListingProduct($listingProduct);

        $this->setAjaxContent($productSearchMenuBlock);

        return $this->getResult();
    }
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage\ViewVariationsGridAjax
 */
class ViewVariationsGridAjax extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $grid = $this->createBlock('Walmart_Listing_Product_Variation_Manage_Tabs_Variations_Grid');
        $grid->setListingProduct($this->walmartFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}

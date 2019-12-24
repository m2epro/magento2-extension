<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\RemoveAddedProducts
 */
class RemoveAddedProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        foreach ($listingProductsIds as $listingProductId) {
            try {
                $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);
                $listingProduct->delete();
            } catch (\Exception $e) {
            }
        }

        $this->getListing()->setSetting('additional_data', 'adding_listing_products_ids', []);
        $this->getListing()->setSetting('additional_data', 'adding_new_asin_listing_products_ids', []);
        $this->getListing()->setSetting('additional_data', 'auto_search_was_performed', 0);
        $this->getListing()->save();

        return $this->_redirect('*/amazon_listing_product_add/index', [
            'step' => 2,
            'id' => $this->getRequest()->getParam('id'),
            '_query' => [
                'source' => $this->getHelper('Data\Session')->getValue('products_source')
            ],
            'wizard' => $this->getRequest()->getParam('wizard')
        ]);
    }
}

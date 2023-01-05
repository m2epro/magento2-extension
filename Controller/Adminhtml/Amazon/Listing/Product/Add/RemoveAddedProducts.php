<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class RemoveAddedProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        if (!empty($listingProductsIds) && is_array($listingProductsIds)) {
            $this->deleteListingProducts($listingProductsIds);
        }

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

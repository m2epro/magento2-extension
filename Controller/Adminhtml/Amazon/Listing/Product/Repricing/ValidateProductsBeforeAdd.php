<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing\ValidateProductsBeforeAdd
 */
class ValidateProductsBeforeAdd extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    public function execute()
    {
        $listingId   = $this->getRequest()->getParam('id');
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        if (empty($productsIds)) {
            $this->getMessageManager()->addError($this->__('Products not selected.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        $result = $this->validateregularPrice($productsIds);

        if (count($result) === 0) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this
                    ->__('Products with B2B Price can not be managed via Amazon Repricing Tool.
                    Only Products with B2C Price can be repriced.'),
                'products_ids' => $result
            ]);
            return $this->getResult();
        }

        $popup = $this->createBlock(
            'Amazon_Listing_View_Sellercentral_Repricing_RegularPricePopup'
        );

        $this->setJsonContent([
            'title' => $this->__('Attention!'),
            'html' => $popup->toHtml(),
            'products_ids' => $result
        ]);
        return $this->getResult();
    }

    private function validateRegularPrice($productsIds)
    {
        $tableAmazonListingProduct = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product')
            ->getResource()
            ->getMainTable();

        $select = $this->resourceConnection->getConnection()->select();

        // selecting all products with online_regular_price
        $select->from($tableAmazonListingProduct, 'listing_product_id')
            ->where('online_regular_price IS NOT NULL');

        $select->where('listing_product_id IN (?)', $productsIds);

        return $this->resourceConnection->getConnection()->fetchCol($select);
    }
}

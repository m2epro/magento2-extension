<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description\GetRandomMagentoProductId
 */
class GetRandomMagentoProductId extends Description
{
    public function execute()
    {
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();

        $offset = rand(0, $listingProductCollection->getSize() - 1);
        $listingProductCollection
            ->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['id', 'product_id'])
            ->joinLeft(
                ['ml' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                '`ml`.`id` = `main_table`.`listing_id`',
                ['store_id']
            )
            ->limit(1, $offset);

        $storeId = $this->getRequest()->getPost('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $listingProductCollection
            ->addFieldToFilter('store_id', $storeId)
            ->getFirstItem();

        if ($listingProduct->getId() !== null) {
            $this->setJsonContent([
                'success' => true,
                'product_id' => $listingProduct->getProductId()
            ]);
            return $this->getResult();
        }

        $productCollection = $this->productModel->getCollection();

        $offset = rand(0, $productCollection->getSize() - 1);
        $productCollection
            ->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns('entity_id')
            ->limit(1, $offset);

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $productCollection->getFirstItem();

        if ($product->getId() !== null) {
            $this->setJsonContent([
                'success' => true,
                'product_id' => $product->getId()
            ]);
        } else {
            $this->setJsonContent([
                'success' => false,
                'message' => $this->__('You don\'t have any products in Magento catalog.')
            ]);
        }

        return $this->getResult();
    }
}

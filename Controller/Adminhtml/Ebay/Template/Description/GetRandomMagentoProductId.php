<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

class GetRandomMagentoProductId extends Description
{
    public function execute()
    {
        $storeId = $this->getRequest()->getPost('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $productId = $this->getProductIdFromListing($storeId) ?? $this->getProductIdFromMagento();

        if ($productId) {
            $this->setJsonContent([
                'success'    => true,
                'product_id' => $productId,
            ]);
        } else {
            $this->setJsonContent([
                'success' => false,
                'message' => $this->__('You don\'t have any products in Magento catalog.'),
            ]);
        }

        return $this->getResult();
    }

    private function getProductIdFromListing($storeId): ?int
    {
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collectionSize = $listingProductCollection->getSize();

        if ($collectionSize == 0) {
            return null;
        }

        $offset = rand(0, $collectionSize - 1);
        $listingProductCollection
            ->getSelect()
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['id', 'product_id'])
            ->joinLeft(
                ['ml' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                '`ml`.`id` = `main_table`.`listing_id`',
                ['store_id']
            )
            ->limit(1, $offset);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $listingProductCollection
            ->addFieldToFilter('store_id', $storeId)
            ->getFirstItem();

       return $listingProduct->getId();
    }

    private function getProductIdFromMagento(): ?int
    {
        $productCollection = $this->productModel->getCollection();
        $collectionSize = $productCollection->getSize();

        if ($collectionSize == 0) {
            return null;
        }

        $offset = rand(0, $collectionSize - 1);
        $productCollection
            ->getSelect()
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('entity_id')
            ->limit(1, $offset);

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $productCollection->getFirstItem();
        return $product->getId();
    }
}

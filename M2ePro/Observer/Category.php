<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

/**
 * Class \Ess\M2ePro\Observer\Category
 */
class Category extends AbstractModel
{
    //########################################

    public function process()
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->getEventObserver()->getData('category');

        $categoryId = (int)$category->getId();
        $websiteId = (int)$category->getStore()->getWebsiteId();

        $changedProductsIds = $this->getEventObserver()->getData('product_ids');
        $postedProductsIds = array_keys($this->getEventObserver()->getData('category')->getData('posted_products'));

        if (!is_array($changedProductsIds) || count($changedProductsIds) <= 0) {
            return;
        }

        $websitesProductsIds = [
            // website for default store view
            0 => $changedProductsIds
        ];

        if ($websiteId == 0) {
            foreach ($changedProductsIds as $productId) {
                $productModel = $this->modelFactory->getObject('Magento\Product')->setProductId($productId);
                foreach ($productModel->getWebsiteIds() as $websiteId) {
                    $websitesProductsIds[$websiteId][] = $productId;
                }
            }
        } else {
            $websitesProductsIds[$websiteId] = $changedProductsIds;
        }

        foreach ($websitesProductsIds as $websiteId => $productIds) {
            foreach ($productIds as $productId) {

                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->getHelper('Magento\Product')->getCachedAndLoadedProduct($productId);

                /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category $object */
                $object = $this->modelFactory->getObject('Listing_Auto_Actions_Mode_Category');
                $object->setProduct($product);

                if (in_array($productId, $postedProductsIds)) {
                    $object->synchWithAddedCategoryId($categoryId, $websiteId);
                } else {
                    $object->synchWithDeletedCategoryId($categoryId, $websiteId);
                }
            }
        }
    }

    //########################################
}

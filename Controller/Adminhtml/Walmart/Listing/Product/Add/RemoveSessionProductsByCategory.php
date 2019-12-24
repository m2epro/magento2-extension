<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\RemoveSessionProductsByCategory
 */
class RemoveSessionProductsByCategory extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $categoriesIds = $this->getRequestIds();

        $tempSession = $this->getSessionValue('source_categories');
        if (!isset($tempSession['products_ids'])) {
            return $this->getResult();
        }
        /** @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree */
        $treeBlock = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Category_Tree');
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $productsForEachCategory = $treeBlock->getProductsForEachCategory();

        $products = [];
        foreach ($categoriesIds as $categoryId) {
            $products = array_merge($products, $productsForEachCategory[$categoryId]);
        }

        $tempSession['products_ids'] = array_diff($tempSession['products_ids'], $products);

        $this->setSessionValue('source_categories', $tempSession);
    }

    //########################################
}

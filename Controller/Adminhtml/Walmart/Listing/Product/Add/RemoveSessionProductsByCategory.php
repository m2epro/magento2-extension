<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class RemoveSessionProductsByCategory extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    public function execute()
    {
        $categoriesIds = $this->getRequestIds();

        $tempSession = $this->getSessionValue('source_categories');
        if (!isset($tempSession['products_ids'])) {
            return $this->getResult();
        }
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree::class);
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $productsForEachCategory = $treeBlock->getProductsForEachCategory();

        $products = [];
        foreach ($categoriesIds as $categoryId) {
            $products = array_merge($products, $productsForEachCategory[$categoryId]);
        }

        $tempSession['products_ids'] = array_diff($tempSession['products_ids'], $products);

        $this->setSessionValue('source_categories', $tempSession);
    }
}

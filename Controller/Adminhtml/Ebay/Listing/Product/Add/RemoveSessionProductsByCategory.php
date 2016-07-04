<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class RemoveSessionProductsByCategory extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{

    public function execute() {
        $categoriesIds = $this->getRequestIds();

        $tempSession = $this->getSessionValue('source_categories');
        if (!isset($tempSession['products_ids'])) {
            return;
        }
        /* @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree */
        $treeBlock = $this->createBlock('Ebay\Listing\Product\Add\Category\Tree');
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $productsForEachCategory = $treeBlock->getProductsForEachCategory();

        $products = array();
        foreach ($categoriesIds as $categoryId) {
            $products = array_merge($products, $productsForEachCategory[$categoryId]);
        }

        $tempSession['products_ids'] = array_diff($tempSession['products_ids'], $products);

        $this->setSessionValue('source_categories',$tempSession);
    }

}
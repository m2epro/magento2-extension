<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\GetCategoriesSummaryHtml
 */
class GetCategoriesSummaryHtml extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $productsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        /** @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree */
        $treeBlock = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Category_Tree', '');
        $treeBlock->setSelectedIds($productsIds);

        /** @var $block \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Summary\Grid */
        $block = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Category_Summary_Grid', '');
        $block->setStoreId($this->getListing()->getStoreId());
        $block->setProductsIds($productsIds);
        $block->setProductsForEachCategory($treeBlock->getProductsCountForEachCategory());

        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }

    //########################################
}

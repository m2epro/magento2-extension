<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class GetCategoriesSummaryHtml extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $productsIds = !isset($tempSession['products_ids']) ? array() : $tempSession['products_ids'];

        /* @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Category\Tree */
        $treeBlock = $this->createBlock('Amazon\Listing\Product\Add\SourceMode\Category\Tree', '');
        $treeBlock->setSelectedIds($productsIds);

        /* @var $block \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Category\Summary\Grid */
        $block = $this->createBlock('Amazon\Listing\Product\Add\SourceMode\Category\Summary\Grid', '');
        $block->setStoreId($this->getListing()->getStoreId());
        $block->setProductsIds($productsIds);
        $block->setProductsForEachCategory($treeBlock->getProductsCountForEachCategory());

        $this->setAjaxContent($block->toHtml());
        
        return $this->getResult();
    }

    //########################################
}
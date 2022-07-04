<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Summary\Grid;
use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree;

class GetCategoriesSummaryHtml extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $productsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()->createBlock(Tree::class, '');
        $treeBlock->setSelectedIds($productsIds);

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Summary\Grid $block */
        $block = $this->getLayout()->createBlock(Grid::class, '');
        $block->setStoreId($this->getListing()->getStoreId());
        $block->setProductsIds($productsIds);
        $block->setProductsForEachCategory($treeBlock->getProductsCountForEachCategory());

        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }
}

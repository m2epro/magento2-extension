<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Variations\Grid;
use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\ViewVariationsGridAjax
 */
class ViewVariationsGridAjax extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $grid = $this->getLayout()->createBlock(Grid::class);
        $grid->setListingProduct($this->amazonFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}

<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class ViewVariationsGridAjax extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $grid = $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Variations\Grid');
        $grid->setListingProduct($this->amazonFactory->getObjectLoaded('Listing\Product', $productId));

        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
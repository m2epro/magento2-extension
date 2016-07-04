<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Variation\Product\Manage;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('listing_product_id', $productId);
        $view = $this->createBlock('Ebay\Listing\Variation\Product\Manage\View');

        $this->setAjaxContent($view);
        return $this->getResult();
    }
}
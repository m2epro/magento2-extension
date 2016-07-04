<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

class TryToMoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
{
    //########################################

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = $this->parentFactory->getCachedObjectLoaded(
            $componentMode,'Listing',$listingId
        );

        $failedProducts = array();
        foreach ($selectedProducts as $selectedProduct) {
            $listingProductInstance = $this->parentFactory->getObjectLoaded(
                $componentMode,'Listing\Product',$selectedProduct
            );

            if (!$this->productCanBeMoved($listingProductInstance->getProductId(), $listingInstance)) {
                $failedProducts[] = $listingProductInstance->getProductId();
            }
        }

        if (count($failedProducts) == 0) {
            $this->setAjaxContent(json_encode(array('result' => 'success')), false);
        } else {
            $this->setAjaxContent(json_encode(array(
                'result' => 'fail',
                'failed_products' => $failedProducts
            )), false);
        }

        return $this->getResult();
    }

    //########################################
}
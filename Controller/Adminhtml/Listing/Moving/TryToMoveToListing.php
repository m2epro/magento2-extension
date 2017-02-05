<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

class TryToMoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
{
    //########################################

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $selectedProducts = (array)$this->getHelper('Data')->jsonDecode(
            $this->getRequest()->getParam('selectedProducts')
        );
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
            $this->setJsonContent(array('result' => 'success'));
        } else {
            $this->setJsonContent(array(
                'result' => 'fail',
                'failed_products' => $failedProducts
            ));
        }

        return $this->getResult();
    }

    //########################################
}
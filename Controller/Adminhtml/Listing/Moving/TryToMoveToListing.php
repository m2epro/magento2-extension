<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Moving\TryToMoveToListing
 */
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
            $componentMode,
            'Listing',
            $listingId
        );

        $failedProducts = [];
        foreach ($selectedProducts as $selectedProduct) {
            $listingProductInstance = $this->parentFactory->getObjectLoaded(
                $componentMode,
                'Listing\Product',
                $selectedProduct
            );

            if (!$this->productCanBeMoved($listingProductInstance->getProductId(), $listingInstance)) {
                $failedProducts[] = $listingProductInstance->getProductId();
            }
        }

        if (count($failedProducts) == 0) {
            $this->setJsonContent(['result' => 'success']);
        } else {
            $this->setJsonContent([
                'result' => 'fail',
                'failed_products' => $failedProducts
            ]);
        }

        return $this->getResult();
    }

    //########################################
}

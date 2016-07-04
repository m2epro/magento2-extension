<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

class TryToMoveToListing extends Listing
{
    protected $parentFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        Context $context
    )
    {
        $this->parentFactory = $parentFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = $this->parentFactory->getCachedObjectLoaded(
            $componentMode, 'Listing' ,$listingId
        );

        $failedProducts = array();
        foreach ($selectedProducts as $selectedProduct) {
            $otherListingProductInstance = $this->parentFactory->getObjectLoaded(
                $componentMode, 'Listing\Other' ,$selectedProduct
            );

            if (!$listingInstance->getChildObject()->addProductFromOther($otherListingProductInstance,true,false)) {
                $failedProducts[] = $otherListingProductInstance->getProductId();
            }
        }

        $failedProducts = array_values(array_unique($failedProducts));

        if (count($failedProducts) == 0) {
            $this->setJsonContent([
                'result' => 'success'
            ]);
        } else {
            $this->setJsonContent([
                'result' => 'fail',
                'failed_products' => $failedProducts
            ]);
        }

        return $this->getResult();
    }
}
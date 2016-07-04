<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetManagePopup extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');

        if (!$listingProductId) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Listing Product must be specified.')
            ]);

            return $this->getResult();
        }

        $variationManageBlock = $this->createBlock('Amazon\Listing\Product\Variation\Individual\Manage')
            ->setData('listing_product_id', $listingProductId);

        $this->setJsonContent([
            'type' => 'success',
            'html' => $variationManageBlock->toHtml()
        ]);

        return $this->getResult();
    }
}
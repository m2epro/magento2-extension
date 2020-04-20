<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\GetEditSkuPopup
 */
class GetEditSkuPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart_Listing_Product_Sku_Form')
        );

        return $this->getResult();
    }
}

<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\GetEditIdentifiersPopup
 */
class GetEditIdentifiersPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart_Listing_Product_Identifiers_Form')
        );

        return $this->getResult();
    }
}

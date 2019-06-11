<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

class GetEditSkuPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart\Listing\Product\Sku\Form')
        );

        return $this->getResult();
    }
}
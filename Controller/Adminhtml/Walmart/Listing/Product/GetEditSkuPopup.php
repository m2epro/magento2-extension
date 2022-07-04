<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

class GetEditSkuPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Sku\Form::class)
        );

        return $this->getResult();
    }
}

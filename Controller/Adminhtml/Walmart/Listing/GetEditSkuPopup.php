<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class GetEditSkuPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart\Listing\View\Walmart\Sku\Main')
        );

        return $this->getResult();
    }
}
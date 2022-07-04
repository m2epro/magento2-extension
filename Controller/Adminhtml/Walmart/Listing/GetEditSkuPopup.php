<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class GetEditSkuPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Walmart\Sku\Main::class)
        );

        return $this->getResult();
    }
}
